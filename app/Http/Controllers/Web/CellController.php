<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Cell;
use App\Models\Member;
use App\Models\User;
use App\Models\Zone;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;

class CellController extends Controller
{
    public function index()
    {
        $cells = Cell::orderBy('name', 'asc')->get();
        return view('pages.cells.index', compact("cells"));
    }


    public function create()
    {
        $zones = Zone::orderBy("name", "asc")->get();
        $members = Member::orderBy("last_name", "asc")->get();
        $users = User::orderBy("last_name", "asc")->get();
        return view('pages.cells.create', compact("zones", "members", "users"));
    }

    public function store(\Illuminate\Http\Request $request)
    {

        Validator::make($request->all(), [
            "name" => "required",
            "zone_id" => "required",
            "balance" => "required",
            "type" => "required",
            "user_id" => "required",
        ])->validate();

        $cell = Cell::create([
            "code" => (new AppController())->generateUniqueCode(),
            'name' => $request->name,
            'details' => $request->details,
            'location' => $request->location,
            'zone_id' => $request->zone_id,
            'balance' => $request->balance,
            'type' => $request->type,
            'user_id' => $request->user_id,
            'verified' => false,

//            'leader_id' => $request->leader_id != "None" && $request->leader_id != "0" ? $request->leader_id : null,
        ]);

        $user = \App\Models\User::find($request->user_id);
        $user?->member?->update([
            'leader_cell_id' => $cell->id
        ]);

        if ((new AppController())->isApi($request)) {
            return response()->json([
                "code" => $cell->code
            ]);
        } else {
            return Redirect::route('cells.index', ['id' => $cell->id])->with('success', 'Cell created!');
        }
    }

    public function show($code)
    {
        $cell = Cell::where("code", $code)->first();
        if (!is_object($cell))
            return Redirect::back()->with('error', 'Cell not found');
        else {
            $members = Member::where("cell_id", null)->orderBy("last_name", "asc")->get();

            $chartData = [
                "data" => [],
                "labels" => []
            ];

            foreach ($cell->meetings()->orderBy("date", "asc")->get() as $meeting) {
                $chartData["data"][] = $meeting->attendances()->count();
                $chartData["labels"][] = date("m/d/Y", Carbon::createFromTimestamp($meeting->date)->getTimestamp());
            }

//            dd($chartData);

            return view('pages.cells.show', compact('cell', 'members', 'chartData'));
        }
    }

    public function edit($code)
    {
        $cell = Cell::where("code", $code)->first();
        if (!is_object($cell))
            return Redirect::back()->with('error', 'Cell not found');
        else {
            return view('pages.cells.edit', compact('cell'));
        }
    }

    public function update(Request $request, $code)
    {
        $cell = Cell::where("code", $code)->first();
        if (!is_object($cell))
            return Redirect::back()->with('error', 'Cell not found');
        else {
            //update

            Validator::make($request->all(), [
                "first_name" => "required",
                "last_name" => "required",
                "gender" => "required",
            ])->validate();

            $cell->update([
                'first_name' => $request->first_name,
                'middle_name' => $request->middle_name,
                'other_name' => $request->other_name,
                'last_name' => $request->last_name,
                'gender' => $request->gender,
//            'cell_id' => $request->cell_id,
//                'ministry_id' => $request->ministry_id,
            ]);

            return Redirect::route('cells.show', $code)->with('success', 'Cell updated!');
        }
    }

    public function verify(\Illuminate\Http\Request $request)
    {
        $request->validate([
            "code" => "required",
        ]);

        $cell = Cell::where("code", $request->code)->first();
        if (!is_object($cell))
            return Redirect::back()->with('error', 'Cell not found');
        else {

            $cell->update([
                'verified' => true,
            ]);

            if ((new AppController())->isApi($request)) {
                return response()->json([
                    "message" => "Successfully verified!"
                ]);
            } else {
                return Redirect::route('cells.show', $cell->code)->with('success', 'Cell verified!');
            }

        }
    }

    public function trash($code)
    {
        $cell = Cell::where("code", $code)->first();
        if (!is_object($cell))
            return Redirect::back()->with('error', 'Cell not found');
        else {
            $cell->delete();
            return Redirect::route('cells.index')->with('success', 'Cell deleted!');
        }
    }
}
