import { useEffect, useState } from "react";
import Api from '../../Api';

const PostSelector = ({ postType = null, value = "", onChange = () => {} }) => {

  const [search,setSearch] = useState( "" );
  const [suggestions,setSuggestions] = useState( [] );
  const [aborter,setAborter] = useState( null );
  const [page,setPage] = useState( null );

  const find = () => {
    if( !search ) {
      return;
    }

    const form = new FormData();

    form.append( 's', search );
    if( postType ) {
      if( typeof postType === 'string' ) {
        form.append( 't', postType );
      }
      else if ( Array.isArray(postType) ) {
        form.append( 't', postType.join(',') );
      }
    }
    
    if( aborter ) {
      aborter.abort();
    }

    const [request,_aborter] = Api().request( 'search_posts', { body: form } );
    setAborter( _aborter );

    request
    .then( response => response.json() )
    .then( result => {
      setSuggestions( result.data );
    });
  }

  useEffect( () => {
    find();
  }, [search]);

  useEffect( () => {
    if( !value ) {
      return;
    }

    const form = new FormData;
    form.append( 'id', value );

    const [request,_aborter] = Api().request( 'get_post', { body: form } );
    request
    .then( response => response.json() )
    .then( result => {
      if( result.success ) {
        setPage( result.data )
      }
    });
  }, [value]);

  return (
    <>
      { value ? (
        <div className="flex gap-4 font-semibold">
          { page?.post_title ?? value }
          
          <button className="px-2 py-0" 
              onClick={ () => { onChange(null); }}>&times;</button>
        </div> 

        ) : (
        
        <div>
          <input type="search" 
            value={ search } 
            placeholder="Search page..."
            onFocus={ (e) => {
                if( e.target.value ) {
                  find();
                }
              }
            }
            onChange={ (e) => {
                setSearch( e.target.value );
              }
            }
            onBlur={ () => {
                setTimeout( () => setSuggestions([]), 500 );
              }
            } />
          { suggestions.length ? ( <ul className="absolute z-10 bg-white shadow p-4">
              { suggestions.map( s => <li 
                className="hover:underline"
                onClick={ () => {
                  onChange( s );
                  setSuggestions( [] );
                  setSearch( '' );
                  }
                }>{ s.post_title }</li> ) }
            </ul> ) : ''
          }
        </div>
        )
      }
    </>
  );
} 

export default PostSelector;