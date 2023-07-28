// The following code prevents a form from re-submitting on a page refresh.
if ( window.history.replaceState ) {
  window.history.replaceState( null, null, window.location.href );
}
