<?php

namespace App\Http\Controllers;

use App\Models\MasMachine;
use Illuminate\Http\Request;

class MasMachineController extends Controller
{
    public function index()
    {
        $machines = MasMachine::all();
        return response()->json($machines);
    }

    public function store(Request $request)
    {
        $machine = MasMachine::create($request->all());
        return response()->json($machine, 201);
    }

    public function show($id)
    {
        $machine = MasMachine::find($id);
        if ($machine) {
            return response()->json($machine);
        }
        return response()->json(['error' => 'Machine not found'], 404);
    }

    public function update(Request $request, $id)
    {
        $machine = MasMachine::find($id);
        if ($machine) {
            $machine->update($request->all());
            return response()->json($machine);
        }
        return response()->json(['error' => 'Machine not found'], 404);
    }

    public function destroy($id)
    {
        $machine = MasMachine::find($id);
        if ($machine) {
            $machine->delete();
            return response()->json(null, 204);
        }
        return response()->json(['error' => 'Machine not found'], 404);
    }
}

