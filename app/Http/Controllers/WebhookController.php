<?php namespace App\Http\Controllers;

use App\Project;
use App\Deployment;
use App\Commands\QueueDeployment;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * The deployment webhook controller
 */
class WebhookController extends Controller
{
    /**
     * Handles incoming requests from Gitlab or PHPCI to trigger deploy
     *
     * @param string $webhook The webhook hash
     * @return Response
     * @todo Check for input, make sure it is a valid gitlab hook, check repo and branch are correct
     *       http://doc.gitlab.com/ee/web_hooks/web_hooks.html
     */
    public function webhook($hash)
    {
        $project = Project::where('hash', $hash)->firstOrFail();

        $success = false;
        if (!is_null($project) && count($project->servers)) {
            $this->dispatch(new QueueDeployment($project, new Deployment));

            $success = true;
        }

        return Response::json([
            'success' => $success
        ], 200); // FIXME: Surely this should not be 200 on failure
    }

    /**
     * Generates a new webhook URL
     *
     * @param Project $project_id
     * @return Response
     */
    public function refresh($project_id)
    {
        $project = Project::findOrFail($project_id);
        
        $project->generateHash();
        $project->save();

        return Response::json([
            'success' => true,
            'url'     => route('webhook', $project->hash)
        ], 200);
    }
}
