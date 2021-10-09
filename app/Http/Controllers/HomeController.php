<?php

namespace App\Http\Controllers;

use App\Utils\UploadImages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth')->only(['index']);
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('home');
    }

    public function upload(Request $request)
    {
        try {
            $this->validate($request, [
                'image' => ['required', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:5120'],
            ]);
        } catch (ValidationException $e) {
            return response(['status' => false, 'errors' => $e->errors()], $e->status);
        }

        return ['status' => true, 'image' => url(UploadImages::upload('uploads/images', $request->file('image')))];
    }

    public function uploads(){
        return array_map(function($file){
            return ['file' => url($file), 'size' => filesize($file)];
        }, glob('uploads/images/*'));
    }

    public function contactUs(Request $request){
        try {
            $this->validate($request, [
                'name' => ['required', 'string', 'min:3', 'max:255'],
                'phone' => ['required', 'string', 'min:6', 'max:255'],
                'email' => ['required', 'email'],
                'message' => ['required', 'string', 'min:10'],
            ]);
        } catch (ValidationException $e) {
            return response(['status' => false, 'errors' => $e->errors()], $e->status);
        }

        Mail::send('emails.contact', $request->all(), function ($m) use ($request) {
            $m->to('ameerafch@gmail.com', 'Dar Alsaed')
                ->subject($request->input('full_name'), ' trying to contact with us!');
            $m->from('no-reply@darsaed.com','No reply');
        });

        return ['status' => true, 'message' => 'SENT'];
    }

    public function requestService(Request $request)
    {
        try {
            $inputs = $this->validate($request, [
                'full_name' => ['required', 'string', 'min:5', 'max:255'],
                'phone_number' => ['required', 'string', 'min:5', 'max:255'],
                'email' => ['required', 'email'],
                'address' => ['required', 'string', 'min:3', 'max:255'],
                'details' => ['required', 'string'],
                'service_name' => ['required', 'min:3', 'max:255'],
            ]);
        } catch (ValidationException $e) {
            return response(['status' => false, 'errors' => $e->errors()], $e->status);
        }

        if(DB::table("request_services")->insert($request->only(['full_name', 'phone_number', 'email', 'address', 'details', 'service_name'])))
        {
            return ['status' => true, 'message' => 'SENT'];
        }

        return response(null, 204);
    }
    public function requestedServices()
    {
        return DB::table("request_services")->paginate();
    }
    public function requestedService($id)
    {
        if(!is_numeric($id)){
            abort(404);
        }
        $item = DB::table("request_services")->find($id);
        if($item == null){
            abort(404);
        }
        return $item;
    }

}
