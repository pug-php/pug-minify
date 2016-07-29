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
