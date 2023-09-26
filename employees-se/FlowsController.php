<?php

namespace EmployeesSmartEvaluering\Http\Controllers;

use EmployeesSmartEvaluering\Contracts\Employee\Filters\MessagingFlowFiltersRepository;
use EmployeesSmartEvaluering\Contracts\MessagingIterationResponsesRepository;
use EmployeesSmartEvaluering\Models\Employee;
use EmployeesSmartEvaluering\Models\EmployeesAttributesGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use MessagingSmartEvaluering\Contracts\MessagingFlowRepository;
use MessagingSmartEvaluering\Models\MessagingSendingFlow;
use MessagingSmartEvaluering\Models\MessagingSendingIteration;
use SmartEvaluering\Models\User;

class FlowsController
{
    // This is an example of controller from a separate module
    // which uses messaging-se functionality within related user's sender ($user->sender()).
    // The sender is the adapter which is bound within the service provider of the current module.

    public function save(int $flowId, Request $request)
    {
        $flowData = $request->validate(['flowData' => 'required'])['flowData'];

        /** @var User $user */
        $user = Auth::user();
        $flowData = $user->sender()->updateFlowData($flowId, $flowData);

        if(!$request->get('requireHead')) {
            return response([
                'flowData' => $flowData
            ]);
        }

        $completeFlowData = $user->sender()->getCompleteFlowData($flowId);
        return response([
            'flowData'                 => $completeFlowData['flowData'],
            'flowIterationsHead'       => $completeFlowData['flowHead']
        ]);
    }

    public function saveIteration(int $flowId, Request $request, MessagingFlowFiltersRepository $filtersRepository)
    {
        /** @var array $iterationData */
        $iterationData = $request->validate([
            'iteration.text' => 'required'
        ])['iteration'];

        /** @var User $user */
        $user = Auth::user();

        $flowHead = $user->sender()->updateIteration($flowId, $iterationData);
        $isFirstIteration = !isset($iterationData['id']) && !isset($flowHead->next);
        if($isFirstIteration)
            $filtersRepository->syncFlowEmployeesFilters($flowId);

        return response()->json(['flowHead' => $flowHead]);
    }

    public function saveSchedule(int $flowId, Request $request, MessagingFlowRepository $flowRepository)
    {
        /** @var array $scheduleData */
        $scheduleData = $request->validate([
            'schedule.id' => 'required|int'
        ])['schedule'];


        /** @var User $user */
        $user = Auth::user();
        $flowHead = $user->sender()->updateSchedule($flowId, $scheduleData)['flowHead'];

        return response()->json(['flowHead' => $flowHead]);
    }

    public function saveEmployeesFilters(int $flowId, Request $request, MessagingFlowFiltersRepository $filtersRepository)
    {
        $filtersData = $request->validate([
            'filtersData'                       => 'nullable|array',
            'filtersData.*.attributes_group_id' => 'required_with:filtersData|int'
        ])['filtersData'] ?? [];

        $filtersRepository->syncFlowEmployeesFilters($flowId, $filtersData);
        return response()->json($filtersRepository->getFlowEmployeesFilters($flowId)->toArray());
    }

    public function deleteFlow(int $flowId) {
        /** @var User $user */
        $user = Auth::user();

        $user->sender()->deleteFlow($flowId);
        return response()->json([]);
    }

    public function getFlowData(int $flowId) {
        /** @var User $user */
        $user = Auth::user();

        $flowData = $user->sender()->getCompleteFlowData($flowId);
        return response()->json([
            'flowData'                 => $flowData['flowData'],
            'flowIterationsHead'       => $flowData['flowHead']
        ]);
    }

    public function getIterationsForAnalysis(int $flowId) {
        /** @var User $user */
        $user = Auth::user();

        $flowData = $user->sender()->getCompleteFlowData($flowId);
        $iterationsIds = collect($flowData['schedules'])->pluck('iteration')->flatten()->pluck('id');
        return response()->json($iterationsIds);
    }

    public function getIterationAttributesAverages(Request $request, int $iterationId, EmployeesAttributesGroup $attributesGroup, MessagingIterationResponsesRepository $responsesRepository) {
        if($attributesGroup->user_id !== $request->user()->id) abort(403);

        $responsesRepository->loadResponsesAveragesForAttributes($attributesGroup, $iterationId);
        return response()->json($attributesGroup->responsesAttributesAverages);
    }
}