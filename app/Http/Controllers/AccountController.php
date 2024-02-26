<?php

namespace App\Http\Controllers;


use App\Models\User;
use App\Models\Category;
use App\Models\JobType;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\File;




class AccountController extends Controller
{
    //This method will show user registration page
    public function registration()
    {
        return view('front.account.registration');
    }

    //This method will save user in DB
    public function processRegistration(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:5|same:confirm_password',
            'confirm_password' => 'required',
        ]);

        if ($validator->passes()) {

            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->save();

            session()->flash('success', 'You have registered successfully.');

            return response()->json([
                'status' => true,
                'errors' => []
            ]);
        } else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }

    //This method will show user registration page
    public function login()
    {
        return view('front.account.login');
    }

    //For the Authentication
    public function authenticate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->passes()) {
            if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
                return redirect()->route('account.profile');
            } else {
                return redirect()->route('account.login')->with('error', 'Either Email/Password is incorrect');
            }
        } else {
            return redirect()->route('account.login')
                ->withErrors($validator)
                ->withInput($request->only('email'));
        }
    }

    public function profile()
    {

        //To get the id of user which is logged in
        $id = Auth::user()->id;

        //To get the info of the user which is logged in
        // $user = User::where('id',$id)->first();
        // OR
        $user = User::find($id);

        // Passing user info in profile.blade so we can use it there
        return view('front.account.profile', [
            'user' => $user
        ]);
    }

    //To update user profile

    public function updateProfile(Request $request)
    {

        $id = Auth::user()->id;

        $validator = Validator::make($request->all(), [
            'name' => 'required|min:5|max:20',
            //for ensuring that user should not update email with the emailid that already exists
            'email' => 'required|email|unique:users,email,' . $id . ',id'
        ]);

        if ($validator->passes()) {

            $user = User::find($id);
            $user->name = $request->name;
            $user->email = $request->email;
            $user->mobile = $request->mobile;
            $user->designation = $request->designation;
            $user->save();

            session()->flash('success', 'Profile updated successfully.');

            return response()->json([
                'status' => true,
                'errors' => []
            ]);
        } else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('account.login');
    }

    // To update users profile pic
    public function updateProfilePic(Request $request)
    {
        // To validate image with extention

        $id = Auth::user()->id;

        $validator = Validator::make($request->all(), [
            'image' => 'required|image'
        ]);


        if ($validator->passes()) {
            //To save/upload image
            $image = $request->image;
            $ext = $image->getClientOriginalExtension();
            $imageName = $id . '-' . time() . '.' . $ext; // Generating unique name of image Ex:- 3-123123321.png
            $image->move(public_path('/profile_pic/'), $imageName); //Moving image to a particular loacation

            // create new image instance i.e. small thumbnail (800 x 600)
            $sourcePath = public_path('/profile_pic/' . $imageName);
            $manager = new ImageManager(Driver::class);
            $image = $manager->read($sourcePath);

            // crop the best fitting 5:3 (600x360) ratio and resize to 600x360 pixel
            $image->cover(150, 150);
            $image->toPng()->save(public_path('/profile_pic/thumb/' . $imageName));

            // Get the user's current image path
            $user = Auth::user();
            $imagePath = $user->image;

            // Delete old Profile Pic From thumb folders
            $thumbImagePath = public_path('/profile_pic/thumb/' . $imagePath);
            if (File::exists($thumbImagePath)) {
                File::delete($thumbImagePath);
            }

            // Delete old Profile Pic From profile_pic folder
            $profileImagePath = public_path('/profile_pic/' . $imagePath);
            if (File::exists($profileImagePath)) {
                File::delete($profileImagePath);
            }

            User::where('id', $id)->update(['image' => $imageName]); //For updating image in DB

            session()->flash('success', 'Profile picture updated successfully.');

            return response()->json([
                'status' => true,
                'errors' => []
            ]);
        } else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }

    //To create jobs
    public function createJob() {

        // To fetch categories & jobTypes from DB to show them in dropdown
        $categories = Category::orderBy('name', 'ASC')->where('status',1)->get();

        $jobTypes = JobType::orderBy('name','ASC')->where('status',1)->get();

        return view('front.account.job.create',[
            'categories' => $categories,
            'jobTypes' => $jobTypes
        ]);
    }


    //Function to save jobs in DB
    public function saveJob(Request $request) {

        $rules = [
            'title' => 'required|min:5|max:200',
            'category' => 'required',
            'jobType' => 'required',
            'vacancy' => 'required|integer',
            'location' => 'required|max:50',
            'description' => 'required',
            'company_name' => 'required|min:3|max:75',
        ];

        $validator = Validator::make($request->all(),$rules);

        if ($validator->passes()) {

            //Create a model to create_jobs 
            $job = new Job();
            $job->title = $request->title;
            $job->category_id = $request->category;
            $job->job_type_id = $request->jobType;
            //To get the id of user which is logged in
            $job->user_id = Auth::user()->id;
            $job->vacancy = $request->vacancy;
            $job->salary = $request->salary;
            $job->location = $request->location;
            $job->description = $request->description;
            $job->benefits = $request->benefits;
            $job->responsibility = $request->responsibility;
            $job->qualification = $request->qualification;
            $job->keywords = $request->keywords;
            $job->experience = $request->experience;
            $job->company_name = $request->company_name;
            $job->company_location = $request->company_location;
            $job->company_website = $request->website;
            $job->save();

            session()->flash('success', 'Job added successfully.');

            return response()->json([
                'status' => true,
                'errors' => []
            ]);

        } else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }

    //Fetching the Jobs from DB
    public function myJobs() {
        
        //the 'paginate(10);' will show 10 records on a single page
        //To use any relation we have to use with() clause, below we used relation with('jobType') which is created in the job model
        $jobs = Job::where('user_id',Auth::user()->id)->with('jobType')->paginate(10);
        
        return view('front.account.job.my-jobs',[
            'jobs' => $jobs
        ]);
    }

    // To show an edit Job forms & show previously filled job data
    public function editJob(Request $request, $id) {
        
        // To fetch categories & jobTypes from DB to show them in dropdown
        $categories = Category::orderBy('name', 'ASC')->where('status',1)->get();
        $jobTypes = JobType::orderBy('name','ASC')->where('status',1)->get();

        //Fetching the job info
        $job = Job::where([
            'user_id' => Auth::user()->id,
            'id' => $id
        ])->first(); //To restrict from accessing the job of other user

        if ($job == null) {
            abort(404);
        }

        return view('front.account.job.edit',[
            'categories' => $categories,
            'jobTypes' => $jobTypes,
            'job' => $job,
        ]);
    }

    //Function to update jobs in DB
    public function updateJob(Request $request, $id) {

            $rules = [
                'title' => 'required|min:5|max:200',
                'category' => 'required',
                'jobType' => 'required',
                'vacancy' => 'required|integer',
                'location' => 'required|max:50',
                'description' => 'required',
                'company_name' => 'required|min:3|max:75',
            ];
    
            $validator = Validator::make($request->all(),$rules);
    
            if ($validator->passes()) {
    
                //Create a model to update_jobs 
                $job = Job::find($id); //Find method is used to find
                $job->title = $request->title;
                $job->category_id = $request->category;
                $job->job_type_id = $request->jobType;
                //To get the id of user which is logged in
                $job->user_id = Auth::user()->id;
                $job->vacancy = $request->vacancy;
                $job->salary = $request->salary;
                $job->location = $request->location;
                $job->description = $request->description;
                $job->benefits = $request->benefits;
                $job->responsibility = $request->responsibility;
                $job->qualification = $request->qualification;
                $job->keywords = $request->keywords;
                $job->experience = $request->experience;
                $job->company_name = $request->company_name;
                $job->company_location = $request->company_location;
                $job->company_website = $request->website;
                $job->save();
    
                session()->flash('success', 'Job updated successfully.');
    
                return response()->json([
                    'status' => true,
                    'errors' => []
                ]);
    
            } else {
                return response()->json([
                    'status' => false,
                    'errors' => $validator->errors()
                ]);
            }
    }

}
