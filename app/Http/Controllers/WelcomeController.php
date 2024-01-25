<?php
/**
 * Created by PhpStorm.
 * User: Roja
 * Date: 26-12-2022
 * Time: 05:15
 * Validate inputs ,created DB for individual school and registed user details in config and school DB
 */
namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Models\CommunicationAttachments;
use App\Models\Communications;
use App\Models\NewsEventsAttachments;
use App\Models\NewsEvents;
use File;

class WelcomeController extends Controller
{    
    public function welcome()
    {
        return view('login');
    }

    public function file_upload($school_code,$files,$notification_id,$attachment_type,$target_file,$ext)
    {
        $path = public_path('uploads/'.$school_code.$target_file);//

        if(!File::isDirectory($path)){ //check path already exists
            File::makeDirectory($path, 0777, true, true);
        }

        // Insert attachment details in attachment table
        $index = 0;
        foreach($files as $file)
        {
            $attachment = new CommunicationAttachments;
            $attachment->communication_id = $notification_id;

            // $name = explode('.',$file->getClientOriginalName());
            // $filename = str_replace(["-",","," ","/"], '_', $name[0]);
            // $names = $filename.time().'.'.$name[1];
            // $file->move(public_path().'/uploads/'.$school_code, $names);

            $data = base64_decode($file);
            $name = 'file'.''.time().'.'.$ext[$index];
            $file = public_path().'/'.env('SAMPLE_CONFIG_URL').$school_code.$target_file.$name;
            file_put_contents($file, $data);

            $attachment->attachment_name = $name;
            $attachment->attachment_type =$attachment_type;  //1-image,2-audio,3-document
            $attachment->attachment_location = url('/').'/'.env('SAMPLE_CONFIG_URL').$school_code.$target_file;
            $attachment->save();
            $index++;
        }
       
    }

    public function newsevents_file_upload($school_code,$files,$newsevents_id,$attachment_type,$target_file,$ext)
    {
        $path = public_path('uploads/'.$school_code.$target_file);//

        if(!File::isDirectory($path)){ //check path already exists
            File::makeDirectory($path, 0777, true, true);
        }
            
        if($newsevents_id!='')//get already existing images
        {
            $images_list = NewsEvents::where('id',$newsevents_id)->pluck('images')->first();
            if($images_list!='')
            {
                $attachment_id = explode(',',$images_list);
            }
        }
        $index = 0;
        foreach($files as $file) //loop to insert images
        {   
            $attachment = new NewsEventsAttachments;
            $attachment->news_events_id = $newsevents_id;

            // $name = explode('.',$file->getClientOriginalName());
            // $filename = str_replace(' ', '_', $name[0]);
            // $names = $filename.time().'.'.$name[1];
            // $file->move(public_path().'/uploads/'.$school_code, $names);  

            $data = base64_decode($file);
            $name = 'news_events'.''.time().'.'.$ext[$index];
            $file = public_path().'/'.env('SAMPLE_CONFIG_URL').$school_code.$target_file.$name;
            file_put_contents($file, $data);

            $attachment->attachment_name = $name;
            $attachment->attachment_type = $attachment_type;  //1-image
            $attachment->attachment_location = url('/').'/'.env('SAMPLE_CONFIG_URL').$school_code.$target_file;
            $attachment->save();
            $attachment_id[]= $attachment->id;
            $index++;
        }
        return $attachment_id;
    }

    public function profile_file_upload($school_code,$files,$attachment_type,$target_file,$ext)
    {
        $path = public_path('/'.env('SAMPLE_CONFIG_URL').$school_code.$target_file);//

        if(!File::isDirectory($path)){ //check path already exists
            File::makeDirectory($path, 0777, true, true);
        }
        // $name = explode('.',$files->getClientOriginalName())[0];
        // $image = $name.''.time().'.'.$files->extension();
        // $files->move(public_path().'/'.env('SAMPLE_CONFIG_URL').$school_code.$target_file, $image);
        // return url('/').'/'.env('SAMPLE_CONFIG_URL').$school_code.$target_file.$image;

        $data = base64_decode($files);
        $name = 'profile_image'.''.time().'.'.$ext;
        $file = public_path().'/'.env('SAMPLE_CONFIG_URL').$school_code.$target_file.$name;
        file_put_contents($file, $data);
        return url('/').'/'.env('SAMPLE_CONFIG_URL').$school_code.$target_file.$name;
    }
}