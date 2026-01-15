<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TaskAttachmentController extends Controller
{
    /**
     * Subir un archivo a una tarea
     */
    public function store(Request $request, Task $task)
    {
        // 1. Validar si la tarea permite adjuntos
        if (!$task->allow_attachments) {
            return response()->json(['message' => 'Esta tarea no permite archivos adjuntos'], 403);
        }

        // 2. Validar archivo
        $request->validate([
            'file' => 'required|file|max:10240', // Max 10MB
        ]);

        $file = $request->file('file');
        
        // 3. Guardar en disco (public)
        // Se crea en storage/app/public/attachments
        $path = $file->store('attachments', 'public');

        // 4. Crear registro en BD
        $attachment = $task->attachments()->create([
            'user_id' => $request->user()->id,
            'name' => $file->getClientOriginalName(),
            'file_path' => $path, // Ej: attachments/xyz.pdf
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);

        return response()->json([
            'message' => 'Archivo subido correctamente',
            'data' => $attachment
        ], 201);
    }

    /**
     * Eliminar archivo
     */
    public function destroy(TaskAttachment $attachment)
    {
        // Validar permisos (solo quien lo subió o admin)
        // Aquí asumimos abierto por ahora o implementamos Policy después
        
        // Borrar del disco
        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $attachment->delete();

        return response()->json(['message' => 'Archivo eliminado']);
    }

    /**
     * URL de descarga
     */
}
