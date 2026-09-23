<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use Illuminate\Http\Request;

class IncidentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $incidents = Incident::all();
        return view('incident.index', compact('incidents'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('incident.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'reporter' => 'required',
            'type' => 'required',
            'status' => 'required'
        ]);

        Incident::create($request->all());
        return redirect()->route('incident.index');

    }

    /**
     * Display the specified resource.
     */
    public function show(Incident $incident)
    {
        
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Incident $incident)
    {
        return view('incident.create',compact($incident));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Incident $incident)
    {
         $request->validate([
            'reporter' => 'required',
            'type' => 'required',
            'status' => 'required'
        ]);
        $request->update($request->all());
        return redirect()->route('incident.index');

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Incident $incident)
    {
        $incident->delete();
        return redirect()->route('incident.index');
    }
}
