import { useEffect, useState } from 'react';
import Api from '../Api';

const Posts = ({ loading = ()=>{}, saving = ()=>{} }) => {
  const [hosts, setHosts] = useState( [] );
  const [types, setTypes] = useState( [] );
  const [config, setConfig ] = useState( null );
  const [autosave,setAutosave] = useState( false );

  function get(){
    loading( true );
    const [request,aborter] = Api().request( 'post_types' );

    request
    .then( response => response.json() )
    .then( result => {

      if( result?.data ) {
        setHosts( result.data.hosts );
        setTypes( result.data.types ); 
        setConfig( result.data.config ); 
      }
    })
    .finally( () => {
      loading( false );
    })
  } // get

  function save(){
    var form_data = new FormData();

    for ( let type in config ) {
      form_data.append(`${type}`, config[type]);
    }

    saving( true );
    const [request,aborter] = Api().request( 'post_types_save', { body: form_data } );

    request
    .then( response => response.json() )
    .then( result => {
      console.log( result );
      
      if( result.success ) {
      }
    })
    .finally( () => {
      saving( false );
    })    
  } // save 

  function setOverride( postType, host ) {
    const cfg = JSON.parse( JSON.stringify( config ) );

    if( !cfg ) {
      cfg = {};
    }

    if( !host ) {
      // remove config
      if( cfg && cfg[ postType ] ) {
        delete cfg[ postType ];
      }      
    }
    else {
      if( typeof cfg[ postType ] === 'undefined' ) {
        cfg[ postType ] = {};
      }

      cfg[ postType ] = host;
    }

    setConfig( cfg );
  } // setOverride

  useEffect( () => {
    get();
  }, []);

  useEffect( () => {
    if( autosave ) {
      save();
    }
    else if( config ) {
      setAutosave( true );
    }
  }, [config]);

  return ( <div>
    <div className="flex gap-4 justify-between">
      <span className="text-lg">List of post types</span>

      <button className="border bg-white button"
        onClick={() => get() }>Reload</button>
    </div>

    <div>
      <table>
        <thead>
          <tr>
            <th>Post type</th>
            <th>Host / Domain</th>
          </tr>
        </thead>

        <tbody>
          { types.map( t => (
            <tr>
              <td>
                <span>{ t.label }</span>
                &nbsp;
                <code>{ t.name }</code>
              </td>
              <td>
                <select value={ config[ t.name ] } 
                  onChange={ (e) => setOverride( t.name, e.target.value )  }>
                  <option value="">Don't override</option>
                  { hosts.map( h => <option value={ h }>{ h }</option> ) }
                </select>
              </td>
            </tr> 
            )
          ) }
        </tbody>
      </table>
    </div>


    <button 
      className="button button-primary fixed right-4 bottom-8 hidden"
      onClick={ () => { save() }}>Save</button>
  </div> );
}

export default Posts;