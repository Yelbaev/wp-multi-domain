
import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/editPost';
import { __ } from '@wordpress/i18n';

import { useState } from '@wordpress/element';

registerPlugin( 'wpmd-host-metabox', {
  render(){
    const [ value, setValue ] = useState( wpmd_post?.host );
    const [ aborter, setAborter ] = useState( null );
    const [ nonce, setNonce ] = useState( wpmd_post.nonce );

    const save = ( e ) => {

      setValue( e.target.value );
      const data = new FormData;

      data.append('action', 'wpmd-save-host');
      data.append('post_ID', wpmd_post.ID );
      data.append( wpmd_post.field, e.target.value );
      data.append( nonce.name, nonce.value );
      
      if( aborter && !aborter.signal.aborted ) {
        aborter.abort();
      }
      
      let newAborter = new AbortController();

      fetch( ajaxurl, {
        method: "POST",
        body: data,
        signal: newAborter.signal
      })
      .then( (response) => {
        if( !response.ok ) {
          alert( 'Error' );
          return;
        }
        
        response.json().then( (result) => {
          if( !result.status ) {
            alert( result.error || "Unknown error" );
            return;
          }

          if( result.nonce ) {
            setNonce( result.nonce );
          }
        });

      });

      setAborter( newAborter );      
    };

    const options = wpmd_post.hosts.map( h => {
      return <option value={ h }>{ h }</option>;
    })

    return (
      <PluginDocumentSettingPanel 
        name="wpmd-host-panel"
        title={ __('Multi domain host', 'wpmd') }
        icon={ null }
        open={ false }>

        <div id="wpmd-host">
          <select onChange={ save } 
            value={ value }
            style={{width: '100%'}}>
            <option value="">{ __('Default Host', 'wpmd') }</option>
            { options }
          </select>
        </div>
      </PluginDocumentSettingPanel>
    )
  }
} );

