<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Hash;
use Auth;
use Session;
use App\User;
use App\Hours;
use App\Menu;
use App\Review;
use App\Restaurant;

class RestaurantsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $restaurants = Restaurant::all();
        return \View::make('restaurants')->with("restaurants", $restaurants);

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function showCreate()
    {
        if(Auth::user() && Auth::user()->isAdmin()) {
            // display the view for creating new restaurant
            return \View::make("addrestaurant");
        } else {
            // go back home pls
            return Redirect::to("main");
        }
    }

    /**
     * actually create the new resource
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // create the restaurant from the admin panel
         $rules = array(
                'restaurant_name'	=>	'required',
                'street_address'	=>	'required',
                'city'	=>	'required',
                'state' => 'required',
                'website'   =>  'required',
            );

    	// kick off validator instance for our registration page
    	$validator = Validator::make(Input::all(), $rules);

        if($validator->fails()) {
    		return Redirect::to('addrestaurant')
    			->withErrors($validator)
    			->withInput();
    	} else {
            // new restaurant instance
            $new_restaurant = new Restaurant;
            
            // populate with information from form
            $new_restaurant->restaurant_name = Input::get('restaurant_name');
            $new_restaurant->street_address = Input::get('street_address');
            $new_restaurant->city = Input::get('city');
            $new_restaurant->state = Input::get('state');
            $new_restaurant->website = Input::get('website');

            // save the model of the newly created restaurant
            $new_restaurant->save();

            Session::flash("restaurant_added", "Restaurant successfully added.");
            return Redirect::to('restaurants');
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // get all of the restaurants, reviews, and menu pertaining to selected restaurant
        $restaurants = Restaurant::findOrFail($id);
        $reviews = Review::where('restaurant_id', '=', $id)->get();
        $menu = Menu::where('restaurant_id', '=', $id)->get();
        $hours = Hours::where('restaurant_id', '=', $id)->get();
        // get the avg rating of course
        $avg_rating = $reviews->avg('rating');
        return \View::make('showrestaurant')
                ->with("restaurants", $restaurants)
                ->with("reviews", $reviews)
                ->with("avg_rating", $avg_rating)
                ->with("menu", $menu)
                ->with("hours", $hours);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showMyReviews()
    {
        if(Auth::check()) {
            // pass in the id again from the previous route, so it knows what to do with it
            $reviews = Review::where('user_id', '=', Auth::user()->user_id)->get();
            return \View::make('myreviews')->with("reviews", $reviews);
        } else {
            // go back home pls
            return Redirect::to("main");
        }
    }

    /**
     * Display the form for creating a review
     *
     */
    public function showReview($id) {
        if(Auth::check()) {
            // pass in the id again from the previous route, so it knows what to do with it
            $restaurants = Restaurant::findOrFail($id);
            return \View::make('addreview')->with("restaurants", $restaurants);
        } else {
            // go back home pls
            return Redirect::to("main");
        }
    }

    public function createReview($id) {
        // again, fetching id of restaurant cus that's what we want
       $restaurants = Restaurant::findOrFail($id);
       $rules = array(
    		'rating'	=>	'required',
    		'title'	=>	'required',
    		'review'	=>	'required',
    	);

    	// kick off validator instance for our registration page
    	$validator = Validator::make(Input::all(), $rules);

    	// check to see if the validator fails
    	if($validator->fails()) {
    		return Redirect::to('restaurants/'. $restaurants->restaurant_id .'/addreview')
    			->withErrors($validator)
    			->withInput();
    	} else {
    		// if validator succeeds, add the user to our database and store their credentials
    		$new_review = new Review;
            // get the ID of the currently logged in user, so we can associate review
            $user = Auth::user();
            $user_id = $user->id;

            $new_review->user_id = Auth::user()->user_id;
    		$new_review->rating = Input::get("rating");
    		$new_review->review_tagline = Input::get("title");
    		$new_review->review = Input::get("review");

            // associate the review with the user and save to db
    		$new_review->restaurant()->associate($id);
            $new_review->save();

    		// redirect to the login page, and display account successfully created message
    		Session::flash("review_created", "Review successfully added");
    		return Redirect::to("restaurants/$id");
    	}
    }

    /**
     * Show the form for editing the restaurant.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showEdit($id)
    {
        //
        if(Auth::user() && Auth::user()->isAdmin()) {
            // display the view for creating new restaurant
            $restaurants = Restaurant::findOrFail($id);
            return \View::make('editrestaurant')->with("restaurants", $restaurants);
        } else {
            // go back home pls
            return Redirect::to("main");
        }
    }

    /**
     * actually edit the restaurant info
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
       $restaurants = Restaurant::findOrFail($id);
       $rules = array(
    		'restaurant_name'	=>	'required',
            'street_address'	=>	'required',
            'city'	=>	'required',
            'state' => 'required',
            'website'   =>  'required',
    	);

    	// kick off validator instance for our registration page
    	$validator = Validator::make(Input::all(), $rules);

    	// check to see if the validator fails
    	if($validator->fails()) {
    		return Redirect::to('restaurants/'. $restaurants->restaurant_id .'/edit')
    			->withErrors($validator)
    			->withInput();
    	} else {
            // retrieve the currently selected restaurant
            $edit_restaurant = Restaurant::findOrFail($id);

            // start making changes
            $edit_restaurant->restaurant_name = Input::get('restaurant_name');
            $edit_restaurant->street_address = Input::get('street_address');
            $edit_restaurant->city = Input::get('city');
            $edit_restaurant->state = Input::get('state');
            $edit_restaurant->website = Input::get('website');

            // save the changes to the model
            $edit_restaurant->save();

            // flash the message saying that everything's ok
            Session::flash("restaurant_edited", "Restaurant successfully edited.");
            
            return Redirect::to("restaurants/".$id);
        }
    }

     /**
     * show form to add hours to store location
     *
     * @param  int  $id
     */
    public function showAddHours($id)
    {
        //
        if(Auth::user() && Auth::user()->isAdmin()) {
            // display the view for creating new restaurant
            $restaurants = Restaurant::findOrFail($id);
            return \View::make("addhours")->with("restaurants", $restaurants);
        } else {
            // go back home pls
            return Redirect::to("main");
        }
    }

    /**
     * actually add the hours into the Hours model
     *
     * @param  int  $id
     */
    public function addHours($id)
    {
        //
       $restaurants = Restaurant::findOrFail($id);
       $rules = array(
    		'day'	=>	'required',
            'time_open'	=>	'required',
            'time_closed'	=>	'required',
    	);

    	// kick off validator instance for our registration page
    	$validator = Validator::make(Input::all(), $rules);

    	// check to see if the validator fails
    	if($validator->fails()) {
    		return Redirect::to('restaurants/'. $restaurants->restaurant_id .'/addhours')
    			->withErrors($validator)
    			->withInput();
    	} else {
            // create instance with new hours
            $new_hours = new Hours;
            $new_hours->day = Input::get("day");
            $new_hours->time_open = Input::get("time_open");
            $new_hours->time_closed = Input::get("time_closed");

            // save the new hours and associate to restaurant
            $new_hours->restaurant()->associate($id);
            $new_hours->save();

            // flash the message that the add was successful
            Session::flash("hours_success", "Hours updated successfully.");
            // return the redirect to the next person
            return Redirect::to("restaurants/".$id);
        }
        
    }

    /**
     * show form to add menu item to the restaurant
     *
     * @param  int  $id
     */
    public function showAddMenuItem($id)
    {
        //
        if(Auth::user() && Auth::user()->isAdmin()) {
            // display the view for creating new restaurant
            $restaurants = Restaurant::findOrFail($id);
            return \View::make("addmenuitem")->with("restaurants", $restaurants);
        } else {
            // go back home pls
            return Redirect::to("main");
        }
    }

    /**
     * show form to add hours to store location
     *
     * @param  int  $id
     */
    public function addMenuItem($id)
    {
        //
       $restaurants = Restaurant::findOrFail($id);
       $rules = array(
    		'item_name'	=>	'required',
            'menu_price'	=>	'required|regex:/^\d*(\.\d{1,2})?$/',
            'menu_description'	=>	'required',
    	);

    	// kick off validator instance for our registration page
    	$validator = Validator::make(Input::all(), $rules);

    	// check to see if the validator fails
    	if($validator->fails()) {
    		return Redirect::to('restaurants/'. $restaurants->restaurant_id .'/addmenuitem')
    			->withErrors($validator)
    			->withInput();
    	} else {
            // kick off menu model
            $new_menu = new Menu;
            
            // start adding input to the model
            $new_menu->item_name = Input::get("item_name");
            $new_menu->menu_price = Input::get("menu_price");
            $new_menu->menu_description = Input::get("menu_description");
            
            // associate this with the restaurant we're adding them to
            $new_menu->restaurant()->associate($id);
            $new_menu->save();

            // flash the message that the add was successful
            Session::flash("menu_success", " Menu item added.");
            // return the redirect to the next person
            return Redirect::to("restaurants/".$id);
        }
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

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
