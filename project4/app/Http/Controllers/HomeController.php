<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Redirect;
use Auth;
use Session;

class HomeController extends Controller
{
    // creating the first function for logging in
    public function showLogin() {
        if(Auth::check()) {
            return Redirect::to("main");
        } else {
            return \View::make('login');
        }
    }

    public function performLogin() {
        // start off with validation rules
        $rules = array(
            'username' => 'required|alphaNum',
            'password' => 'required|alphaNum',
        );

        $validator = Validator::make(Input::all(), $rules);

        // if the validator fails, redirect to login page with flash message
        if($validator->fails()) {
            return Redirect::to('login')
                ->withErrors($validator)
                ->withInput(Input::except('password'));
        } else {
            $user_data = array(
                'username' => Input::get('username'),
                'password' => Input::get('password'),
            );

            if(Auth::attempt($user_data)) {
                Session::flash('message', "Successfully logged in.");
                return Redirect::to("main");
            } else {
                Session::flash('message', "Incorrect username or password. Please try again.");
                return Redirect::to("login");
            }
        }
    }

    // perform the logout functionality when clicked
    public function performLogout() {
        // kill the session and log user out of application
        Auth::logout();
        Session::flash('logged_out', "Successfully logged out.");
        return Redirect::to("login");
    }

}
