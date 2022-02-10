<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GenerateCommandList extends Controller
{
    private $names = [];
    private $commands = [];
    private $filled_tasks = [];
    private $original_tasks = [];
    private $filename = "";

    public function index(Request $request){
        $valid_filename_regex = "/^[^\/\?\*:;{}\\\]+\.[^\/\?\*:;{}\\\]+$/";
        $validated = $request->validate([
            'filename' => 'required|string|regex:'.$valid_filename_regex,
            'tasks' => 'required|array',
        ]);

        $this->original_tasks = $validated['tasks'];
        $this->filename = $validated["filename"];

        $tasks = $this->fillNoDependencyTasks();

        if(!$this->isCompletelyFilled()){
             $this->fillOtherTasks($tasks);
        }

        return $this->createFile($this->commands);

    }

    private function fillNoDependencyTasks(){
        $output = [];
        foreach($this->original_tasks as $item){
            if(!array_key_exists('dependencies',$item)){
                array_push($this->commands,$item['command']);
                array_push($this->names,$item['name']);
                array_push($this->filled_tasks,$item);

            }else{
                array_push($output,$item);
            }
        }
        return $output;
    }

    private function fillTasksWithDependencies(array $input){
        $output = [];
        foreach($input as $item){
            if(array_diff($item['dependencies'],$this->names) === []){
                array_push($this->commands,$item['command']);
                array_push($this->names,$item['name']);
                array_push($this->filled_tasks,$item);
            }else{
                array_push($output,$item);
            }
        }
        return $output;
    }



    private function fillOtherTasks(array $input){
        $temp_array = [];
        $temp_array = $this->fillTasksWithDependencies($input);
        if(!$this->isCompletelyFilled()){
            $this->fillOtherTasks($temp_array);
        }
    }

    private function isCompletelyFilled(){
        if(sizeof($this->filled_tasks) === sizeof($this->original_tasks)){
            return true;
        }else{
            return false;
        }
    }

    private function createFile(array $input){
        if(!($myfile = fopen("files/".$this->filename, "w"))){
            return "Unable to open file!";
        }
        for($i = 0; $i < sizeof($input); $i++){
            $text = $input[$i]."\n";
            fwrite($myfile, $text);
        }

        fclose($myfile);

        return $this->downloadFile();
    }

    private function downloadFile()
    {
    	$filePath = public_path("files/".$this->filename);

    	return response()->download($filePath, $this->filename);
    }



}
