import logo from './logo.svg';
import './App.css';
import { useState } from 'react';

import Dashboard from "./tabs/Dashboard";
import Menus from "./tabs/Menus";
import Posts from "./tabs/Posts";

const tabs = [
  { name: "Dashboard", component: Dashboard },
  { name: "Menus", component: Menus },
  { name: "Posts", component: Posts },
]

function App() {
  const [tab,setTab] = useState( 0 );

  const [isSaving,setIsSaving] = useState( 0 );
  const [isLoading,setIsLoading] = useState( 0 );

  const modSaving = ( state ) => {
    const value = isLoading + ( state ? 1 : -1 );
    setIsSaving( value < 0 ? 0 : value );
  }
  const modLoading = ( state ) => {
    const value = isLoading + ( state ? 1 : -1 );
    setIsLoading( value < 0 ? 0 : value );
  }  

  const TabComponent = tabs[ tab ] && tabs[ tab ].component ? tabs[ tab ].component : <>-</>

  return (
    <div className="wpmd">
      <div className="wpmd-tabs">
        {tabs.map( (t,i) => (
          <a href={'#' + encodeURIComponent(t.name) }
            className={ 'px-2 py-1 ' + ( i === tab ? 'font-bold bg-white rounded-t rounded-b-none' : '' ) }
            onClick={ (e) => {
              e.preventDefault();
              setTab(i)
            }}>
            { t.name }
          </a>
        ))}
      </div>
      
      { ( isSaving > 0 || isLoading > 0 ) ? 
            <div class="wpmd-loader">
              { isLoading > 0 ? <span>Loading...</span> : null }
              { isSaving > 0 ? <span>Saving...</span> : null }
            </div> 
            : null
      }

      <div className="wpmd-tab p-4 bg-white">
        <TabComponent saving={ modSaving } loading={ modLoading } />
      </div>
    </div>
  );
}


export default App;
