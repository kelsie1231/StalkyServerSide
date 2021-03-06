<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\User;
use App\Rating;
use DB;

class UserController extends Controller
{
    /**
     * Given: a list of ids that were searched
     * Want: return all users with matching characters
     *
     * @param String $id
     * @return \Illuminate\Http\Response
     */
    public function search($id)
    {
        //
        $users = User::where('name', 'like', "%{$id}%")->get();
        $json = array();
        foreach ($users as $user) {
            $json[] = array(
                'name' => $user->name,
                'ratings' => $user->getAverageRatings(),
                'facebook_id' => $user->facebook_id
            );

        }

        return response()->json( $json );
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
        $user = User::where('facebook_id',$id)->first();

        return response()->json( array(
        	'facebook_id' => $user->facebook_id,
        	'ratings' => $user->getAverageRatings(),
            'comments' => $user->getComments(), 
        	"name"=> $user->name)
        );

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }
    
    public function top( $sort_type, $limit = 5 ) {
    	$selectors = array( 'facebook_id', 'name' );
    		// no type specified, fetches overall average
    		$selector = '0';
    		foreach( RATING::$RATING_TYPES as $type ) {
    			$query = sprintf( 'sum( %s ) / count( * )', 
    				RATING::RATING_COLUMN_PREFIX . $type 
    			);
    			$selector .= '+ ' . $query;
    			$selectors[] = DB::raw( $query . 'as ' . $type );
    			
    		}
    		$selectors[] = DB::raw( 
    			sprintf( '(%s)/%d as avg', $selector, count( RATING::$RATING_TYPES ) )
    		);
    		
    	$results = User::join( 'ratings', 'ratings.user_id_to', '=', 'users.facebook_id' )
    		->select( $selectors )
    		->groupBy( 'facebook_id' )
    		->orderBy( in_array($sort_type, RATING::$RATING_TYPES) ? $sort_type : 'avg' , 'desc' )
    		->take( $limit )
    		->get();
    	return response()->json( $results );
    }

}
