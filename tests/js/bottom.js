/** @jsx dom */
var dom = React.createElement;

ReactDOM.render(dom(
  "h1",
  null,
  "Hello, ",
  dom(
    "em",
    null,
    "world!"
  ),
  " ",
  dom(
    "button",
    { className: "btn btn-primary" },
    "OK"
  )
), document.getElementById('main'));

//# sourceMappingURL=test.js.map

(function($) {
  return $('h1').html("<strong>Foo</strong><em>bar</em><ul>\n<li>!</li>\n<li>?</li>\n</ul>");
})(jQuery);

