<?php
/**
 * Dpokuwiki CMK plugin
 * Copyright (C) 2013 Elie Roux <elie.roux@telecom-bretagne.eu>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * --------------------------------------------------------------------
 */

if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC.'lib/plugins/');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_cmk extends DokuWiki_Syntax_Plugin {
    /**
     * @return string Syntax mode type
     */
    public function getType() {
      return 'formatting';
    }
    /**
     * @return array Allowed nested types
     */
    public function getAllowedTypes() {
      return array('formatting', 'substition', 'disabled');
    }
    /**
     * @return string Paragraph type
     */
    public function getPType() {
        return 'normal';
    }
    /**
     * @return int Sort order - Low numbers go before high numbers
     *
     * CMK Markup rendering should be able to use other patters so this is
     * low.
     */
    public function getSort() {
        return 55;
    }
    /**
     * Seems to be the default, but it's particularly important with NSBPC.
     */
    public function isSingleton() {
        return true;
    }
    /**
     * Override loadConfig() in order to get usual plugin config + nsbpc.
     *
     * This sets $this->conf to a table organized this way:
     *   - ['list']: the list of all possible markups, in all rendering modes. 
     *               It is useful as, in the Lexer, we don't know rendering mode
     *               yet, so we have to parse all possible markups.
     *   - ['xhtml']: an associative array with xhtml markup as keys and their
     *                replacement as values.
     *   - ['rmode1']: idem for rmode rendering mode 1 (texit for example).
     *   - etc.
     *
     * Please note that the configuration in conf/ directory must have the same
     * structure (see the example in the plugin zip).
     *
     * Note also that the 'list' element of the array prevents a rendering mode
     * with this name...
     */
    function loadConfig(){
      if ($this->configloaded) {return;}
      parent::loadConfig(); // fills $this->conf with usual plugin config,
	  // shouldn't be usefule here, but who knows...
	  // Now we have to check and parseconf/cmk.ini in the plugin directory
	  $defaultconffilename = DOKU_PLUGIN.'cmk/conf/cmk.ini';
	  if (is_readable($defaultconffilename)) {
	    $defaultconf = parse_ini_file($defaultconffilename, true);
        if (is_array($this->conf)) {
		  $this->conf = array_replace($defaultconf, $this->conf);
		} else {
          $this->conf = $defaultconf;
		}
	  }
      $nsbpc = $this->loadHelper('nsbpc');
      $currentns = getNS(cleanID(getID()));
      $nsbpconf = $nsbpc->getConf($this->getPluginName(), $currentns, true);
      $this->conf['list'] = array(); // the list of all possible arrays.
      foreach ($nsbpconf as $mode => $markups) {
        if (!is_array($markups) ||
             ($this->conf[$mode] && !is_array($this->conf[$mode]))) {
          // we should raise a warning here, but I don't see any error reporting
          // mechanism in dokuwiki...
          continue;
        }
        if ($this->conf[$mode]) {
          $this->conf[$mode] = array_replace($this->conf[$mode], $markups);
        } else {
          $this->conf[$mode] = $markups;
        }
        // in order to fill mklist in an efficient way, we first make it the
        // concatenation of all conf tables (this will give an array with the
        // values we want as keys), then we just have to array_keys() it...
        $this->conf['list'] = array_replace($this->conf['list'],
                                            $this->conf[$mode]);
      }
      $this->conf['list'] = array_keys($this->conf['list']);
    }
    /**
     * This explodes config values for a particular rendering mode. For example
     * if we have 
     *    conf[xhtml][key] == "<span class='myclass'>:::</span>"
     * this function will change it to
     *    conf[xhtml][key] == array("<span class='myclass'>","</span>")
     *
     * We use a separate function for optimization: the work will be done only
     * once, for one rendering mode only. We cannot do it in loadConfig
     * because we don't know the rendering mode yet.
     */
    function explodeConfig($mode)
    {
      if (is_array($this->conf[$mode])) {
        $conf = &$this->conf[$mode];
        foreach ($conf    as $key=>$value) {
          $conf[$key] = explode(':::', $value);
        }
      }
    }
    /**
     * Override default accepts() method to allow nesting
     * - ie, to get the plugin accepts its own entry syntax.
     *
     * Taken from Wrap plugin.
     */
    function accepts($mode) {
      if ($mode == 'plugin_cmk') return true;
      return parent::accepts($mode);
    }
    /**
     * Connect lookup pattern to lexer.
     *
     * @param string $mode Parser mode
     */
    public function connectTo($mode) {
      $this->loadConfig();
      foreach ($this->conf['list'] as $mk) {
        $this->Lexer->addEntryPattern('<'.$mk.'>(?=.*?</'.$mk.'>)',$mode,
          'plugin_cmk');
      }
    }
    public function postConnect() {
      foreach ($this->conf['list'] as $mk) {
        $this->Lexer->addExitPattern('</'.$mk.'>','plugin_cmk');
      }
    }
    /**
     * Handle matches of the cmk syntax
     *
     * @param string $match The match of the syntax
     * @param int    $state The state of the handler
     * @param int    $pos The position in the document
     * @param Doku_Handler    $handler The handler
     * @return array Data for the renderer
     */
    public function handle($match, $state, $pos, &$handler){
      $data = false;
      switch ($state) {
        case DOKU_LEXER_ENTER:
          $data = substr($match,1,-1);
          break;
        case DOKU_LEXER_UNMATCHED:
          // This is taken from Wrap, to allow nesting.
          $handler->_addCall('cdata', array($match), $pos);
          // in this case we don't need to return anything
          // to the renderer, only cmk markups have meaning
          // here...
          return false;
          break;
        case DOKU_LEXER_EXIT:
          $data = substr($match,2,-1);
          break;
        default:
          return false;
          break;
      }
      return array($state, $data);
    }
    /**
     * same as $this->configloaded but for use with explodeConfig
     */
    var $configexploded = false;
    /**
     * Renderer
     *
     * @param string         $mode      Renderer mode (supported modes: all)
     * @param Doku_Renderer  $renderer  The renderer
     * @param array          $data      The data from the handler() function
     * @return bool If rendering was successful.
     */
    public function render($mode, &$renderer, $data) {
      if (empty($data)) return false;
      if (!$this->configexploded) {
        $this->explodeConfig($mode);
        $this->configexploded = true;
      }
      $conf = $this->conf[$mode];
      list($state, $markup) = $data;
      if (!is_array($conf) || empty($conf[$markup])) {
        return false;
      }
      switch ($state) {
        case DOKU_LEXER_ENTER:
          $renderer->doc .= $conf[$markup][0];
          break;
        case DOKU_LEXER_EXIT:
          $renderer->doc .= $conf[$markup][1];
          break;
        default:
          break;
      }
      return true;
    }
}
?>
