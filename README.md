## Custom Markups for Dokuwiki

This plugin is a very simple and direct way to create your own markups in Dokuwiki. The idea is to input semantic data instead of rendering data, and then to choose the rendering in the different rendering modes of Dokuwiki. This plugin allows you to define arbitrary custom markups, with no constraints, so that you have to check the availability and the potential conflicts of your markups.

### Configuration format

For simplicity and straightforwardness goals, this plugin uses config files with [php.net](ini syntax), just like [nbspc], with which it is compatible.
A cmk config file looks like this (see also the [example](example)):

```ini
[xhtml]
mymarkup = "<span class='mymarkup'>:::</span>"
```

Let's explain this:
 * the `[xhtml]` first line is a section in the configuration, telling the rendering mode for which the following config lines will apply. See next section for details.
 * the second line indicates the configuration for the markup *mymarkup*. The `:::` delimits what will be the replacement for `<mymarkup>` and the replacement for `</mymarkup>` in the page.

So with this example, when dokuwiki will encounter

```xml
text <mymarkup>more text</mymarkup> some text again
```

it will output

```xml
text <span class="mymarkup">more text</span> some text again
```

### Configuration file

The default configuration file is in the `conf/` directory of the plugin, it is called `cmk.ini`. You can also add per-namespace configuration through [nbspc], configuration pages will then be called `nsbpc_cmk`.

### Priority

The plugin has a very high priority (55) in the [priorities](syntax priorities), which means you can use syntax from other plugins (for example [wrap]) as replacements for your custom markups.

### Other rendering modes

*xhtml* is the default rendering mode of Dokuwiki, the one used when a page is displayed. Other rendering modes are set up by plugins, for instance [texit] for PDF export through LaTeX. If you want to add a meaning for your markups in another rendering mode, you can simply add a section in your config files for it. For instance:

```ini
[xhtml]
mymarkup = "<span class='mymarkup'>:::</span>"

[texit]
mymarkup = \mytexmacro{:::}
```

to map *mymarkup* to *mytexmacro* in TeX.

### Limitations

Please see [php.net] for the limitations of the `parse_ini_files` function according to your version of PHP. It should work well with PHP >= 5.3.

Note that the only supported markups are opening/closing markups (`<mymarkup>foo</mymarkup>`), not singles ones (`<mymarkup/>`).

### Requirements

This plugin is very simple and should work with any version of Dokuwiki. A bug has appeared (see comments of the `handle` function of [syntax.php]), so future versions of Dokuwiki might have a different behaviour on this.

### License

This plugin is licensed under the GPLv2+ license.

[php.net]: http://php.net/manual/fr/function.parse-ini-file.php
[nbspc]: https://github.com/eroux/dokuwiki-plugin-cmk
[texit]: https://github.com/eroux/dokuwiki-plugin-dokutexit
[priorities]:https://www.dokuwiki.org/devel:parser:getsort_list
[wrap]:https://www.dokuwiki.org/plugin:wrap
[syntax.php]:https://github.com/eroux/dokuwiki-plugin-cmk/blob/master/syntax.php
