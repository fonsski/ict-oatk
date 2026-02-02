<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\EquipmentServiceHistory;
use App\Http\Requests\StoreEquipmentServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EquipmentServiceController extends Controller
{
    
     * Display a listing of all service records for specific equipment

    public function index(Equipment $equipment)
    {
        
        if (!Auth::check() || !Auth::user()->canManageEquipment()) {
            abort(403, "У вас нет прав на просмотр истории обслуживания оборудования");
        }

        $equipment->load(['status', 'category', 'room']);
        $serviceHistory = $equipment->serviceHistory()
            ->with('performedBy')
            ->orderBy('service_date', 'desc')
            ->paginate(15);

        return view('equipment.service.index', compact('equipment', 'serviceHistory'));
    }

    
     * Show the form for creating a new service record

    public function create(Equipment $equipment)
    {
        
        if (!Auth::check() || !Auth::user()->canManageEquipment()) {
            abort(403, "У вас нет прав на добавление записей об обслуживании оборудования");
        }

        $equipment->load(['status', 'category', 'room']);
        $serviceTypes = [
            'regular' => 'Плановое обслуживание',
            'repair' => 'Ремонт',
            'diagnostic' => 'Диагностика',
            'cleaning' => 'Чистка',
            'update' => 'Обновление ПО',
            'calibration' => 'Калибровка',
            'other' => 'Другое',
        ];

        $serviceResults = [
            'success' => 'Успешно',
            'partial' => 'Частично выполнено',
            'failed' => 'Не выполнено',
            'pending' => 'Требуется дополнительное обслуживание',
        ];

        return view('equipment.service.create', compact('equipment', 'serviceTypes', 'serviceResults'));
    }

    
     * Store a newly created service record

    public function store(StoreEquipmentServiceRequest $request, Equipment $equipment)
    {
        
        if (!Auth::check() || !Auth::user()->canManageEquipment()) {
            abort(403, "У вас нет прав на добавление записей об обслуживании оборудования");
        }

        $validatedData = $request->validated();

        
        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('equipment_service_attachments/' . $equipment->id, 'public');
                $attachments[] = [
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ];
            }
        }

        
        $serviceRecord = new EquipmentServiceHistory([
            'equipment_id' => $equipment->id,
            'service_date' => $validatedData['service_date'],
            'service_type' => $validatedData['service_type'],
            'description' => $validatedData['description'],
            'performed_by_user_id' => Auth::id(),
            'next_service_date' => $validatedData['next_service_date'],
            'service_result' => $validatedData['service_result'],
            'problems_found' => $validatedData['problems_found'],
            'problems_fixed' => $validatedData['problems_fixed'],
            'attachments' => $attachments,
        ]);

        $serviceRecord->save();

        
        $equipment->update([
            'last_service_date' => $validatedData['service_date'],
            'service_comment' => $validatedData['description'],
        ]);

        return redirect()->route('equipment.service.index', $equipment)
            ->with('success', 'Запись об обслуживании успешно добавлена');
    }

    
     * Display the specified service record

    public function show(Equipment $equipment, EquipmentServiceHistory $service)
    {
        
        if (!Auth::check() || !Auth::user()->canManageEquipment()) {
            abort(403, "У вас нет прав на просмотр истории обслуживания оборудования");
        }

        
        if ($service->equipment_id !== $equipment->id) {
            abort(404);
        }

        $service->load('performedBy');

        return view('equipment.service.show', compact('equipment', 'service'));
    }

    
     * Show the form for editing the specified service record

    public function edit(Equipment $equipment, EquipmentServiceHistory $service)
    {
        
        if (!Auth::check() || !in_array(optional(Auth::user()->role)->slug, ["admin", "master"])) {
            abort(403, "Только администраторы и мастера могут редактировать записи об обслуживании");
        }

        
        if ($service->equipment_id !== $equipment->id) {
            abort(404);
        }

        $serviceTypes = [
            'regular' => 'Плановое обслуживание',
            'repair' => 'Ремонт',
            'diagnostic' => 'Диагностика',
            'cleaning' => 'Чистка',
            'update' => 'Обновление ПО',
            'calibration' => 'Калибровка',
            'other' => 'Другое',
        ];

        $serviceResults = [
            'success' => 'Успешно',
            'partial' => 'Частично выполнено',
            'failed' => 'Не выполнено',
            'pending' => 'Требуется дополнительное обслуживание',
        ];

        return view('equipment.service.edit', compact('equipment', 'service', 'serviceTypes', 'serviceResults'));
    }

    
     * Update the specified service record

    public function update(Request $request, Equipment $equipment, EquipmentServiceHistory $service)
    {
        
        if (!Auth::check() || !in_array(optional(Auth::user()->role)->slug, ["admin", "master"])) {
            abort(403, "Только администраторы и мастера могут редактировать записи об обслуживании");
        }

        
        if ($service->equipment_id !== $equipment->id) {
            abort(404);
        }

        $validatedData = $request->validate([
            'service_date' => 'required|date',
            'service_type' => 'required|string',
            'description' => 'required|string|max:1000',
            'next_service_date' => 'nullable|date',
            'service_result' => 'required|string',
            'problems_found' => 'nullable|string|max:1000',
            'problems_fixed' => 'nullable|string|max:1000',
            'remove_attachments' => 'nullable|array',
            'new_attachments' => 'nullable|array',
            'new_attachments.*' => 'file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png|max:10240',
        ]);

        
        $attachments = $service->attachments ?? [];

        
        if ($request->has('remove_attachments')) {
            foreach ($request->input('remove_attachments') as $index) {
                if (isset($attachments[$index])) {
                    
                    if (isset($attachments[$index]['path'])) {
                        Storage::disk('public')->delete($attachments[$index]['path']);
                    }
                    unset($attachments[$index]);
                }
            }
            
            $attachments = array_values($attachments);
        }

        
        if ($request->hasFile('new_attachments')) {
            foreach ($request->file('new_attachments') as $file) {
                $path = $file->store('equipment_service_attachments/' . $equipment->id, 'public');
                $attachments[] = [
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ];
            }
        }

        
        $service->update([
            'service_date' => $validatedData['service_date'],
            'service_type' => $validatedData['service_type'],
            'description' => $validatedData['description'],
            'next_service_date' => $validatedData['next_service_date'],
            'service_result' => $validatedData['service_result'],
            'problems_found' => $validatedData['problems_found'],
            'problems_fixed' => $validatedData['problems_fixed'],
            'attachments' => $attachments,
        ]);

        
        $latestService = $equipment->serviceHistory()->latest('service_date')->first();
        if ($latestService && $latestService->id === $service->id) {
            $equipment->update([
                'last_service_date' => $validatedData['service_date'],
                'service_comment' => $validatedData['description'],
            ]);
        }

        return redirect()->route('equipment.service.show', [$equipment, $service])
            ->with('success', 'Запись об обслуживании успешно обновлена');
    }

    
     * Remove the specified service record

    public function destroy(Equipment $equipment, EquipmentServiceHistory $service)
    {
        
        if (!Auth::check() || !in_array(optional(Auth::user()->role)->slug, ["admin", "master"])) {
            abort(403, "Только администраторы и мастера могут удалять записи об обслуживании");
        }

        
        if ($service->equipment_id !== $equipment->id) {
            abort(404);
        }

        
        if (!empty($service->attachments)) {
            foreach ($service->attachments as $attachment) {
                if (isset($attachment['path'])) {
                    Storage::disk('public')->delete($attachment['path']);
                }
            }
        }

        
        $service->delete();

        
        $latestService = $equipment->serviceHistory()->latest('service_date')->first();
        if ($latestService) {
            $equipment->update([
                'last_service_date' => $latestService->service_date,
                'service_comment' => $latestService->description,
            ]);
        } else {
            $equipment->update([
                'last_service_date' => null,
                'service_comment' => null,
            ]);
        }

        return redirect()->route('equipment.service.index', $equipment)
            ->with('success', 'Запись об обслуживании успешно удалена');
    }

    
     * Download attachment from service record

    public function downloadAttachment(Equipment $equipment, EquipmentServiceHistory $service, $index)
    {
        
        if (!Auth::check() || !Auth::user()->canManageEquipment()) {
            abort(403, "У вас нет прав на просмотр истории обслуживания оборудования");
        }

        
        if ($service->equipment_id !== $equipment->id) {
            abort(404);
        }

        
        if (!isset($service->attachments[$index])) {
            abort(404);
        }

        $attachment = $service->attachments[$index];
        $path = $attachment['path'];
        $original_name = $attachment['original_name'];

        
        if (!Storage::disk('public')->exists($path)) {
            abort(404, 'Файл не найден');
        }

        return Storage::disk('public')->download($path, $original_name);
    }
}
