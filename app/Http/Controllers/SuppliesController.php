<?php

namespace App\Http\Controllers;

use App\Models\supply;
use Illuminate\Http\Request;

class SuppliesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Supply::all();
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'quantity' => 'required|integer',
            'price' => 'required|numeric',
            'barcode'=>'required|string',
            'time'=>'required|date_format:Y-m-d H:i:s'
        ]);
        $sale=Supply::create($validatedData);
        return response()->json($sale, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\sale  $sales
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|\Illuminate\Http\Response
     */
    public function show($id)
    {
        $supply=Supply::findOrFail($id);
        return $supply;
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\sale  $sales
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $sale = Supply::findOrFail($id);
        $sale->fill($request->except(['id','barcode']));
        $sale->save();
        $sale->makeHidden(['id']);
        return response()->json($sale);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\sale  $sales
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $sale = Supply::findOrFail($id);
        if($sale->delete()) return response(null, 200);
    }
}
