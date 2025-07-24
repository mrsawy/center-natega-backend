<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use App\Models\file;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\Log;

class FileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        return "test";
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:15120'],
            "parent_id" => ["nullable", "string"]
        ]);

        $parent = file::find($validated['parent_id']);
        if (!$parent) {
            throw new Exception("Parent file not found");
        }


        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            $customName = $originalName . '.' . $extension;
            $storagePath = 'uploads' . "/" . $parent->path() . "/" . $customName;

            if (Storage::disk()->exists($storagePath)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'A file with the same name already exists.',
                ], 409); // 409 Conflict
            }


            $path = $file->storeAs($storagePath);
            $url = asset('storage/' . $path);
            $uploadedFile = [
                'path' => $path,
                'url' => $url,
            ];



            $createdFile = file::create([
                'id' => Str::slug($customName . Str::uuid()->toString()),
                'name' => $customName,
                'type' => 'file',
                'parent_id' => $parent->id,
            ]);

            return response()->json([
                'status' => 'success',
                'files' => $uploadedFile,
            ]);
        }



        return response()->json(['status' => 'error', 'message' => 'No files uploaded'], 400);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $file = file::with("parent", "children")->find($id);
        if (!$file) {
            return response()->json([
                "status" => "error",
                "message" => "File not found"
            ], 404);
        }
        $file->path = $file->path(); // Add the path to the file object
        // $children = $file->children;


        return response()->json($file);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, file $file)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $file = file::with("parent", "children")->find($id);
        if (!$file) {
            return response()->json([
                "status" => "error",
                "message" => "File not found"
            ], 404);
        }

        // Delete the file from storage
        $storagePath = 'uploads/' . $file->parent->path() . '/' . $file->name;
        if (Storage::disk()->exists($storagePath)) {
            Storage::disk()->delete($storagePath);
        }

        // Delete the file record from the database
        $file->delete();

        return response()->json([
            "status" => "success",
            "message" => "File deleted successfully"
        ]);
    }



    //

}
