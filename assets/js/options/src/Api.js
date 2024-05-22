
function Api(){

  function request( action, params ){
    let aborter = new AbortController();

    const defaults = {
      method: "POST",
      signal: aborter.signal
    }

    const requestParams = { ...defaults, ...params };

    const url = new URL( window?.ajaxurl, window.location.origin );
    url.searchParams.append( 'action', 'wpmd-options-action' );
    url.searchParams.append( 'wpmd-action', action );

    const request = fetch(url.href, requestParams);

    request.catch( e => {
      console.log( e )
    });

    return [request,aborter];
  } // api

  return {
    request
  }
}

export default Api;