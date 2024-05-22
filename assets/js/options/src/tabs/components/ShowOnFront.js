import { useState } from "react";
import PageSelector from './PageSelector';

const ShowOnFront = ({
  host,
  config,
  onChange
}) => {
  
  const circle = <svg xmlns="http://www.w3.org/2000/svg" height="15" width="15" viewBox="0 0 512 512" fill="currentColor"><path d="M464 256A208 208 0 1 0 48 256a208 208 0 1 0 416 0zM0 256a256 256 0 1 1 512 0A256 256 0 1 1 0 256z"/></svg>,
        checked = <svg xmlns="http://www.w3.org/2000/svg" height="15" width="15" viewBox="0 0 512 512" fill="currentColor"><path d="M464 256A208 208 0 1 0 48 256a208 208 0 1 0 416 0zM0 256a256 256 0 1 1 512 0A256 256 0 1 1 0 256zm256-96a96 96 0 1 1 0 192 96 96 0 1 1 0-192z"/></svg>;

  return (
    <div className="flex">
      <div className="flex flex-col">
        <button className="p-2" 
          disabled={ !!config?.is_default }
          onClick={ () => { onChange( null ) } }>
          { !config?.show_on_front ? checked : circle }
          <span>Default</span>
        </button>

        <button className="p-2" 
          disabled={ !!config?.is_default }
          onClick={ () => { onChange( {...config, show_on_front: 'posts', page_on_front: null, page_for_posts: null } ) } }> 
          { config?.show_on_front === 'posts' ? checked : circle }
          Your latest posts	
        </button>

        <button className="p-2 relative" 
          disabled={ !!config?.is_default }
          onClick={ () => { onChange( {...config, show_on_front: 'page' } ) } }>
          { config?.show_on_front === 'page' ? checked : circle }
          <span>A static page</span>

          { config?.show_on_front === 'page' ? <span className="absolute w-12 -right-12 top-1/2 border-t"></span> : null }
        </button>
      </div>

      { config?.show_on_front === 'page' && 
        <ul className="flex flex-col gap-2 border-l ml-12 pl-4">
          <li>
            <label for="page_on_front">
              <span className="font-mono uppercase">Homepage:</span>
              <PageSelector 
                postType="page" 
                onChange={ ( page ) => onChange( {...config, page_on_front: page?.ID ?? "" } ) } 
                value={ config?.page_on_front > 0 ? config?.page_on_front : '' }
                readonly={ !!config?.is_default }
                />
            </label>
          </li>
          <li>
            <label for="page_for_posts">
              <span className="font-mono uppercase">Posts page:</span>
              <PageSelector 
                postType="page" 
                onChange={ ( page ) => onChange( {...config, page_for_posts: page?.ID ?? "" } ) } 
                value={ config?.page_for_posts > 0 ? config?.page_for_posts : '' }
                readonly={ !!config?.is_default }                
                />
            </label>
          </li>
        </ul>
      }
    </div>
  )
}

export default ShowOnFront;