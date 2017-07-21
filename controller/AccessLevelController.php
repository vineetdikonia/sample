<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Request;
use DB;
use URL;
use Hash;

class AccessLevelController extends Controller
{
	public function __construct()
    {
         $this->middleware('auth');
    }

    /**
    * Add / Update Menus
    **/
	public function add_menu($parent_id = 0,$id = NULL)
    {   
        if (Request::isMethod('post')){
            $formData = Request::all();
            $created_at   = date("Y-m-d H:i:s");
            $data = array('parent_id' => $parent_id,'title' => $formData["title"],'icon' => $formData["icon"],'url' => $formData["url"],"created_at" => date("Y-m-d H:i:s"));
            if ($id == NULL) {
                DB::table('menus')->insertGetId($data);
                Request::session()->flash('flash_message', 'Menu has been added.');
            }else{
                DB::table('menus')->where('id', $id)->update($data);
                Request::session()->flash('flash_message', 'Menu has been updated.');
            }
            Request::Session()->flash('alert-class', 'alert-success');
            return redirect('list_menu/'.$parent_id);
        }
        $data =array();
        if ($id != NULL) {
            $data['detail_edit'] = DB::table('menus')->where('id', $id)->first();   
        }

        $data['parent_id'] = $parent_id;
        return view('accesslevel.add_menu',$data);
    }
	
    /**
    * Fetch Data for menu listing
    **/
    public function list_menu($parent_id = 0)
    {   
        if (Request::isMethod('post')){
            $menus  =  DB::table('menus')->select('id', 'parent_id', 'title', 'url')->get(); 
            $datamenu = array();
            $data = array();
            foreach ($menus AS $menu){
                $datamenu['id'] = $menu->id;
                $datamenu['text'] = $menu->title;
                $datamenu['parent_id'] = $menu->parent_id;
                $datamenu['href'] = $menu->url;
                array_push($data, $datamenu); 
            }
            $itemsByReference = array();
            // Build array of item references:
            foreach($data as $key => &$item) {
                $itemsByReference[$item['id']] = &$item;
            }
            // Set items as children of the relevant parent item.
            foreach($data as $key => &$item)  {
                if($item['parent_id'] && isset($itemsByReference[$item['parent_id']])) {
                    $itemsByReference [$item['parent_id']]['nodes'][] = &$item;
                }
            } 
            $dataaaray =array();
            foreach ($data as $key => $value) {
                if($value['parent_id'] == 0){
                    array_push($dataaaray, $data[$key]); 
                }
            }
            echo json_encode($dataaaray);  
            die;
        }
        
        $data['parent_id'] = $parent_id;
        $menu_detail = DB::table('menus')->where('parent_id', $parent_id)->get();
        $menu_ids = array();
        $child_count = array();
        $url = url('/list_menu/');
        foreach ($menu_detail AS $menudetail){
            array_push($menu_ids, $menudetail->id);
        }
        if($parent_id != 0){
            $crumbarray =array();
            $crumbs = $this->createPath($parent_id);
            $excrumbs = explode(">>", $crumbs);
            foreach ($excrumbs as $value) {
                $exarray = explode("|", $value);
                $anchor = "<a href ='$url/$exarray[0]'>$exarray[1]</a>";   
                array_push($crumbarray, $anchor);
                $implodecrumb = implode(" >> ", $crumbarray); 
            }
            $data['parentcrumb']=$implodecrumb;
                
        }else{
            $data['parentcrumb'] = "0";
        }

        $child_menus = DB::table('menus')->whereIn('parent_id', $menu_ids)->get();
        foreach ($child_menus as $child_menu) {
            $child_count[$child_menu->parent_id][] =  $child_menu;
        }
        $data["submenu_count"] = $child_count;
        $data["detail"] =  $menu_detail;
        return view('accesslevel.list_menu', $data);  
    }
    
    /**
    * Delete Menus From Here
    **/
    public function delete_menu($id) {
        //get get all data from data base
        $menus  =  DB::table('menus')->select('id', 'parent_id', 'title', 'url')->get(); 
        $datamenu = array();
        $data = array();
        foreach ($menus AS $menu){
            $datamenu['id'] = $menu->id;
            $datamenu['text'] = $menu->title;
            $datamenu['parent_id'] = $menu->parent_id;
            $datamenu['href'] = $menu->url;
            array_push($data, $datamenu); 
        }
        $itemsByReference = array();
        // Build array of item references:
        foreach($data as $key => &$item) {
            $itemsByReference[$item['id']] = &$item;
        }
        // Set items as children of the relevant parent item.
        foreach($data as $key => &$item)  {
            if($item['parent_id'] && isset($itemsByReference[$item['parent_id']])) {
               $itemsByReference [$item['parent_id']]['nodes'][] = &$item;
            }
        } 
        $mynewarray = collect($data)->map(function ($array) use ($id) {
            if($array['id'] == $id)
            {
            return $array;
            }
        });

        $newtest=array();
        foreach($mynewarray as $row)
        {
            if(is_array($row))
            {
                $newtest=$row;
            }
        }

        array_walk_recursive($newtest, function ($v, $k) { 
            if($k=='id')
            {
                DB::table('menus')->where('id', $v)->delete(); 
            }
        });

        DB::table('menus')->where('id', $id)->delete(); 
        Request::session()->flash('flash_message', 'Menu has been Deleted.');
        Request::Session()->flash('alert-class', 'alert-success');
        return redirect('list_menu');  
    }

}


