<?php

namespace App\Http\Controllers;

use App\Models\file;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use Illuminate\Http\Request;

class FolderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $all_files = file::with("parent", "children")->where("type", "folder")
            ->where("parent_id", null)
            ->get();
        //
        return response()->json([
            "status" => "success",
            "data" => $all_files
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            "name" => "required|string",
            "parent_id" => "nullable|exists:files,id"
        ]);

        $parent = file::find($request->parent_id);

        if (!$parent) {
            $alreadyExisted = file::where("name", $request->name)
                ->whereNull("parent_id")
                ->first();
            if ($alreadyExisted) {
                return response()->json([
                    "status" => "error",
                    "message" => "A folder with the same name already exists at the root level."
                ], 409); // 409 Conflict
            }
        }


        $file = file::create([
            "id" => Str::slug($request->name) . "-" . time(),
            "name" => $request->name,
            "type" => "folder",
            "parent_id" => $request->parent_id
        ]);

        // $request->merge(["id" => $file->id, "type" => $file->type]);
        return response()->json([
            "status" => "success",
            "data" => $file,
            "path" => $file->path()
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
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
    public function update(Request $request, string $id)
    {
        //
        $validation = $request->validate([
            "name" => "required|string"
        ]);

        $file = file::find($id);

        if (!$file) {
            return response()->json([
                "status" => "error",
                "message" => "File not found"
            ], 404);
        }

        // Check if a file with the same name already exists in the same parent folder
        $existingFile = file::where('name', $request->name)
            ->where('parent_id', $file->parent_id)
            ->where('id', '!=', $id) // Exclude the current file
            ->first();

        if ($existingFile) {
            return response()->json([
                'status' => 'error',
                'message' => 'A file with the same name already exists in this folder.'
            ], 409); // 409 Conflict
        }

        $file->name = Str::slug($request->name);
        $file->save();

        return response()->json([
            "status" => "success",
            "data" => $file,
            "path" => $file->path()
        ]);
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
        $file->delete();

        $storagePath = 'uploads' . "/" . $file->path();
        Storage::disk()->deleteDirectory($storagePath);

        return response()->json([
            "status" => "success",
            "message" => "File deleted successfully"
        ]);
    }
};
