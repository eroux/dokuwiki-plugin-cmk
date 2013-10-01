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
     * A list of dokuwiki rendering modes that support cmk. By default, this
     * list contains only 'xhtml'. Currently, the only available rendering mode
     * that supports cmk is texit.
     */
    allowed_rendering_modes = $this->getConf('allowed_rendering_modes');
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
      parent::loadConfig(); // fills $this->conf with usual plugin config 
      $nsbpc = $this->loadHelper('nsbpc');
      $currentns = getNS(cleanID(getID()));
      $this->conf['list'] = array(); // the list of all possible arrays.
      foreach ($this->allowed_rendering_modes as $rmode) {
        $nsbpconf = $nsbpc->getConf($this->getPluginName().$rmode, $currentns);
        if ($this->conf[$rmode]) {
          $this->conf[$rmode] = array_replace($this->conf[$rmode], $nsbpconf);
        } else {
          $this->conf[$rmode] = $nsbpconf;
        }
        // in order to fill mklist in an efficient way, we first make it the
        // concatenation of all conf tables (this will give an array with the
        // values we want as keys), then we just have to array_keys() it...
        array_replace($this->conf['list'], $this->conf);
      }
      $this->conf['list'] = array_keys($this->conf['list']);
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
     *
     * This function exploits an extreme weirdness in the
     * Dokuwiki lexer: in the ENTER state, the $match string
     * seems to be empty, and it's considered to be
     * by PHP. The weird thing is that when you do
     * substr($match, 1, -1), it gives you a valid result...
     * This bug is just weird and I cannot explain it...
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
     * Render xhtml output or metadata
     *
     * @param string         $mode      Renderer mode (supported modes: xhtml)
     * @param Doku_Renderer  $renderer  The renderer
     * @param array          $data      The data from the handler() function
     * @return bool If rendering was successful.
     */
    public function render($mode, &$renderer, $data) {
        if($mode != 'xhtml') return false;
        $renderer->doc .= $renderer->_xmlEntities($text);
        return true;
    }
}
?>
