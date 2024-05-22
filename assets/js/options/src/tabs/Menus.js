import { useEffect, useState } from 'react';
import Api from '../Api';

const Dashboard = ({ loading = ()=>{}, saving = ()=>{} }) => {
  const [hosts, setHosts] = useState( [] );
  const [locations, setLocations] = useState( [] );
  const [menus, setMenus] = useState( [] );
  const [config, setConfig ] = useState( null );
  const [autosave,setAutosave] = useState( false );

  function get(){
    loading( true )
    const [request,aborter] = Api().request( 'menus' );

    request
    .then( response => response.json() )
    .then( result => {

      if( result?.data ) {
        setHosts( result.data.hosts );
        setLocations( result.data.locations ); 
        setMenus( result.data.menus ); 
        setConfig( result.data.config ); 
      }
    })
    .finally( () => {
      loading( false )
    })
  } // get

  function save(){
    var form_data = new FormData();

    for ( let location in config ) {
      for( let host in config[location] ) {
        form_data.append(`${location}[${host}]`, config[location][host]);
      }
    }

    saving( true );
    const [request,aborter] = Api().request( 'menus_save', { body: form_data } );

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

  function setOverride( menuLocation, host, overrideMenuId ) {
    const cfg = JSON.parse( JSON.stringify( config ) );

    if( !cfg ) {
      cfg = {};
    }

    if( !overrideMenuId ) {
      // remove config
      if( cfg && cfg[ menuLocation ] && cfg[ menuLocation ][ host ] ) {
        delete cfg[ menuLocation ][ host ];
      }      
    }
    else {
      if( typeof cfg[ menuLocation ] === 'undefined' ) {
        cfg[ menuLocation ] = {};
      }

      cfg[ menuLocation ][ host ] = overrideMenuId;
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
      <span className="text-lg">List of menus</span>

      <button className="border bg-white button"
        onClick={() => get() }>Reload</button>
    </div>

    <div className="flex gap-8 flex-wrap">
      { locations.map( m => (
        <div>
          <div className="text-lg font-semibold">{ m.label }</div>
          <div className="font-mono uppercase text-sm text-red-800">{ m.location }</div>

          <table class="table-auto">
            <thead>
              <tr>
                <th>Host / Domain</th>
                <th>Menu</th>
              </tr>
            </thead>

            <tbody>
              { hosts.map( h => (
                <tr>
                  <td>{ h }</td>
                  <td>
                    <select value={ config[ m.location ][ h ] } 
                      onChange={ (e) => setOverride( m.location, h, e.target.value )  }>
                      <option value="">Don't override</option>
                      { menus.map( m => <option value={ m.term_id }>{ m.name }</option> ) }
                    </select>
                  </td>
                </tr> 
                )
              ) }
            </tbody>
          </table>
        </div>
        ))
      }
    </div>

    <button
      className="button button-primary fixed right-4 bottom-8 hidden"
      onClick={ () => { save() }}>Save</button>
  </div> );
}

export default Dashboard;