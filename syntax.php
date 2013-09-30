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

    public function isSingleton() {
        return true;
    }

    function loadConfig(){
      if ($this->configloaded) {return;}
      parent::loadConfig(); // fills $this->conf with usual dokuwiki plugin config 
      $nsbpc = $this->loadHelper('nsbpc');
      $nsbpconf = $nsbpc->getConf($this->getPluginName(), getNS(cleanID(getID())));
      if ($this->conf) {
        $this->conf = array_replace($this->conf, $nsbpconf);
      } else {
        $this->conf = $nsbpconf;
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
      foreach ($this->conf as $mk => $value) {
        $this->Lexer->addEntryPattern('<'.$mk.'>(?=.*?</'.$mk.'>)',$mode,'plugin_cmk');
      }
    }

    public function postConnect() {
      foreach ($this->conf as $mk => $value) {
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
