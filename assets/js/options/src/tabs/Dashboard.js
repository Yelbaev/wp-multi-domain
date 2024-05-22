import { useEffect, useState } from 'react';
import Api from '../Api';
import ShowOnFront from './components/ShowOnFront';

const Dashboard = ({ loading = ()=>{}, saving = ()=>{} }) => {
  const [hosts, setHosts] = useState( [] );
  const [config, setConfig] = useState( {} );
  const [domain, setDomain] = useState( "" );

  function getHosts(){
    loading( true );

    const [request,aborter] = Api().request( 'hosts' );

    request
    .then( response => response.json() )
    .then( result => {
      setHosts( result.data );
    })
    .finally( () => {
      loading( false );
    })
  } // getHosts

  function remove( hostname ){
    const fd = new FormData();
    fd.append( 'host', hostname );

    loading( true );

    const [request,aborter] = Api().request( 'host_remove', { body: fd } );

    request
    .then( response => response.json() )
    .then( result => {
      if( result.success ) {
        const newHosts = JSON.parse( JSON.stringify( hosts ) );

        newHosts.splice( newHosts.findIndex( h => h == hostname ), 1 );

        setHosts( newHosts );
      }
      else {
        alert( result?.error ?? "Something went wrong" );
      }
    })
    .finally( () => {
      loading( false );
    })    
  } // remove

  function add(){

    const hostname = domain;

    const fd = new FormData();
    fd.append( 'host', hostname );

    saving( true );
    const [request,aborter] = Api().request( 'host_add', { body: fd } );

    request
    .then( response => response.json() )
    .then( result => {

      if( result.success ) {
        const newHosts = JSON.parse( JSON.stringify( hosts ) );

        newHosts.push( hostname );

        setHosts( newHosts );
        setDomain( "" );
      }
      else {
        alert( result?.error ?? "Something went wrong" );
      }
    })
    .finally( () => {
      saving( false );
    })     

  } // add

  function getHostsConfig(){
    loading( true );

    const [request,aborter] = Api().request( 'hosts_config' );

    request
    .then( response => response.json() )
    .then( result => {
      if( result.success ) {
        setConfig( result.data );
      }
      else {
        alert( result?.error ?? "Something went wrong" );
      }
    })
    .finally( () => {
      loading( false );
    })     
  } // getHostsConfig

  function updateHostsConfig( host, hostCfg ) {
    const cfg = JSON.parse( JSON.stringify( config ) );

    cfg[ host ] = hostCfg;

    setConfig( cfg );
    
    const fd = new FormData();
    for( let h in cfg ) {
      if( cfg[h] ) {
        for( let k in cfg[h] ) {
          fd.append(`config[${h}][${k}]`, cfg[h][k] );
        }
      }
      else {
        // fd.append(`config[${h}]`,);
      }
    }
    // fd.append( 'config', JSON.stringify( cfg ) );

    saving( true );
    const [request,aborter] = Api().request( 'hosts_config_save', { body: fd } );

    request
    .then( response => response.json() )
    .then( result => {
      if( result.success ) {

      }
      else {
        alert( result?.error ?? "Something went wrong" );
      }
    })
    .finally( () => {
      saving( false );
    })     
  } // updateHostsConfig

  useEffect( () => {
    getHosts();
    getHostsConfig();
  }, []);

  return ( <div className="">
    <div className="flex gap-4 justify-between">
      <span className="text-lg">List of hosts</span>
      
      <button className="border bg-white button"
        onClick={() => getHosts() }>Reload</button>
    </div>

    <div className="mb-4">
      <form className="flex gap-4"
        onSubmit={ (e) => {
          e.preventDefault();
          add();
        }}>
        <input type="text" value={domain} onChange={ (e) => setDomain(e.target.value) } />
        <button className="button" type="submit">Add Host</button>
      </form>
    </div>

    <table className="table-auto border-spacing-1">
      <thead>
        <tr>
          <th>Host / Domain</th>
          <th>Show on Front (Homepage)</th>
          <th>&nbsp;</th>
        </tr>
      </thead>

      <tbody>
        { 
        hosts.map( h => ( 
          <tr>
            <td className="align-top font-semibold text-brown">
              <div>{ h }</div>
              <button className="text-red-800 mt-2 px-1 py-0.5 border-red-900" 
                  onClick={ () => remove( h ) }>&times; Remove</button>                
            </td>

            <td className="align-top">
              <ShowOnFront 
                host={h} 
                config={ config[h] } 
                onChange={ (c) => updateHostsConfig( h, c ) } 
                />
            </td>
          </tr> 
        ))
        }
      </tbody>
    </table>

  </div> );
}

export default Dashboard;