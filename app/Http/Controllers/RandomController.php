<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RandomController extends Controller
{

	public function refresh_cache()
	{		
		$lookup = \App\Lookup::refresh_cache();
		return back();
	}



	public function sysswitch($sys)
	{		
		if($sys == 'EID'){
			$new = session(['testingSystem' => 'EID']);
		}else if ($sys == 'Viralload'){
			$new = session(['testingSystem' => 'Viralload']);
		}
		echo json_encode(session('testingSystem'));
	}

	public function search()
	{
		return view('forms.search')->with('pageTitle', 'Search');
	}

	public function config()
	{
		return phpinfo();
	}
}
