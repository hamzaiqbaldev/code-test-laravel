<?php

namespace DTApi\Http\Controllers;

use DTApi\Models\Job;
use DTApi\Http\Requests;
use DTApi\Models\Distance;
use Illuminate\Http\Request;
use DTApi\Repository\BookingRepository;
use DTApi\Http\Controllers\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Class BookingController
 * @package DTApi\Http\Controllers
 */
class BookingController extends Controller
{

    /**
     * @var BookingRepository
     */
    protected $repository;
    private $autheticatedUser;
    /**
     * BookingController constructor.
     * @param BookingRepository $bookingRepository
     */
    public function __construct(BookingRepository $bookingRepository)
    {
        $this->repository = $bookingRepository;
        $this->autheticatedUser = auth()->user();
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        if($user_id = $request->get('user_id')) {

            $response = $this->repository->getUsersJobs($user_id);

        }
        elseif($this->autheticatedUser->user_type == env('ADMIN_ROLE_ID') || $this->autheticatedUser->user_type == env('SUPERADMIN_ROLE_ID'))
        {
            $response = $this->repository->getAll($request);
        }

        return response($response);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function show($id)
    {
        $job = $this->repository->with('translatorJobRel.user')->find($id);

        return response($job);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
        $data = $request->all();

        $response = $this->repository->store($this->autheticatedUser, $data);
        $statusCode = ($response['status'] == 'fail') ? 400: 200;        
        return response($response, $statusCode);

    }

    /**
     * @param $id
     * @param Request $request
     * @return mixed
     */
    public function update($id, Request $request)
    {
        $data = $request->all();        
        //data needs validation before sending to model. have added validation on one/two places for reference
        $response = $this->repository->updateJob($id, array_except($data, ['_token', 'submit']), $this->autheticatedUser);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function immediateJobEmail(Request $request)
    {
        $adminSenderEmail = config('app.adminemail');
        $data = $request->all();

        $response = $this->repository->storeJobEmail($data);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getHistory(Request $request)
    {
        if($user_id = $request->get('user_id')) {

            $response = $this->repository->getUsersJobsHistory($user_id, $request);
            return response($response);
        }

        return null;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function acceptJob(Request $request)
    {
        $data = $request->all();        
        $response = $this->repository->acceptJob($data, $this->autheticatedUser);

        return response($response);
    }

    public function acceptJobWithId(Request $request)
    {
        $data = $request->get('job_id');        
        $response = $this->repository->acceptJobWithId($data, $this->autheticatedUser);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function cancelJob(Request $request)
    {
        $data = $request->all();        
        $validator = Validator::make($request->all(), [
            'job_id' => 'required'            
        ]);
 
        if ($validator->fails()) {
           return response(['status' => 'Fields missing.'], 400);
        }
 
        // Retrieve the validated input...
        $validated = $validator->validated();
        $response = $this->repository->cancelJobAjax($validated, $this->autheticatedUser);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function endJob(Request $request)
    {
        $data = $request->all();
        //All request data needs to be validated before sending to model
        $validator = Validator::make($request->all(), [
            'job_id' => 'required',
            'user_id' => 'required'
        ]);
 
        if ($validator->fails()) {
           return response(['status' => 'Fields missing,'], 400);
        }
 
        // Retrieve the validated input...
        $validated = $validator->validated();
        $response = $this->repository->endJob($validated);

        return response($response);

    }

    public function customerNotCall(Request $request)
    {
        $data = $request->all();

        $response = $this->repository->customerNotCall($data);

        return response($response);

    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getPotentialJobs(Request $request)
    {
        $data = $request->all();        
        $response = $this->repository->getPotentialJobs($this->autheticatedUser);
        if(!$response)
            return response($response, 404);
        
        return response($response);
    }

    public function distanceFeed(Request $request)
    {
        $data = $request->all();
        $distance = (isset($data['distance']) && $data['distance'] != "") ? $data['distance'] : "";
        $time = (isset($data['time']) && $data['time'] != "") ? $data['time'] : "";
        $jobid = (isset($data['jobid']) && $data['jobid'] != "") ? $data['jobid'] : "";
        $session = (isset($data['session_time']) && $data['session_time'] != "") ? $data['session_time'] : "";
        $manually_handled = (isset($data['manually_handled']) && $data['manually_handled'] != "") ? $data['manually_handled'] : "";
        $by_admin = ($data['by_admin'] == true) ? "yes" : "no";
        $admincomment = (isset($data['admincomment']) && $data['admincomment'] != "") ? $data['admincomment'] : "";
      
        if ($time || $distance) {

            $affectedRows = Distance::where('job_id', '=', $jobid)->update(array('distance' => $distance, 'time' => $time));
        }

        if ($admincomment || $session || $flagged || $manually_handled || $by_admin) {
            $affectedRows1 = Job::where('id', '=', $jobid)->update(array('admin_comments' => $admincomment, 'flagged' => $flagged, 'session_time' => $session, 'manually_handled' => $manually_handled, 'by_admin' => $by_admin));
        }

        return response('Record updated!');
    }

    public function reopen(Request $request)
    {
        $data = $request->all();
        $response = $this->repository->reopen($data);

        return response($response);
    }

    public function resendNotifications(Request $request)
    {
        $data = $request->only(['jobid']);
        $job = $this->repository->find($data['job_id']);
        $job_data = $this->repository->jobToData($job);
        $this->repository->sendNotificationTranslator($job, $job_data, '*');

        return response(['success' => 'Push sent']);
    }

    /**
     * Sends SMS to Translator
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function resendSMSNotifications(Request $request)
    {        
        $data = $request->only(['jobid']);
        $job = $this->repository->find($data['job_id']);
        $job_data = $this->repository->jobToData($job);

        try {
            $this->repository->sendSMSNotificationToTranslator($job);
            return response(['success' => 'SMS sent']);
        } catch (\Exception $e) {
            return response(['success' => $e->getMessage()]);
        }
    }

}
