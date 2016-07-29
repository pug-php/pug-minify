/** @jsx dom */
var dom = React.createElement;

ReactDOM.render(
    <h1>Hello, <em>world!</em> <button className="btn btn-primary">OK</button></h1>,
    document.getElementById('main')
);

do ($ = jQuery) ->
  $('h1').html ::"""
      strong Foo
      em bar
      ul
        li !
        li ?
    """

