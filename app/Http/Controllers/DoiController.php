<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\Pool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Constraint\Count;
use GuzzleHttp\Promise\Utils;

class DoiController extends Controller
{
    public function list()
    {
        $providersAndClientsAndDoiCount = [];
        $consortiumId = '';
        return view('dios', compact('providersAndClientsAndDoiCount', 'consortiumId'));
    }

    public function consortiumID(Request $request)
    {
        Log::info('...............start.................');
        $consortiumId = $request->consortiumId;
        $providersAndClientsAndDoiCount = $this->main($consortiumId);
        Log::info('.', [
            '$providersAndClientsAndDoiCount' => Count($providersAndClientsAndDoiCount)
        ]);
        Log::info('...............end.................');

        return view('dios', compact('providersAndClientsAndDoiCount', 'consortiumId'));
    }

    public function main($consortiumId)
    {
        ini_set('max_execution_time', 1200);

        $providers = $this->getProvidersByConsortiumId($consortiumId);
        $providersAndClientsAndDoiCount = $this->getDoisByClientId_asyn($providers);
        return $providersAndClientsAndDoiCount;
    }

    protected function getProvidersByConsortiumId($consortiumId)
    {
        try {
            $providers = Http::get("https://api.datacite.org/providers?consortium-id=$consortiumId")
                ->json('data');

            return $providers;

        } catch (Throwable $exception) {
            return $exception->getMessage();
        }

    }

    protected function getDoisByClientId_asyn($providers)
    {
        try {
            $providersAndClients = collect([]);
            $providersAndClients_rejection = collect([]);
            $i = 0;
            $responses = [];

            foreach ($providers as $provider) {
                $providerId = $provider['id'];
                $clients = $provider['relationships']['clients']['data'];
                $clientIDs = collect([]);
                Log::info('$i', [
                    '$i' => $i,
                    'Count($clients)' => Count($clients)
                ]);
                ++$i;

                $responses = Http::timeout(1200)->pool(fn(Pool $pool) => array_map(
                    function ($client) use ($providersAndClients, $providerId, $pool, $providersAndClients_rejection) {
                        $clientID = $client['id'];
                        Log::info('.', [
                            '$clientID' => $clientID,
                            '$providerId' => $providerId,
                        ]);
                        $pool->timeout(120)->retry(5)->get("https://api.datacite.org/dois?client-id=$clientID")
                            ->then(function ($res) use ($providersAndClients, $providerId, $clientID) {
                                $dois = $res->json('meta');
                                $doisCount = $dois['total'];
                                Log::info('.', [
                                    'clientID' => $clientID
                                ]);
                                $providersAndClients->push([
                                    'providerID' => $providerId,
                                    'clientID' => $clientID,
                                    'doisCount' => $doisCount
                                ]);
                            })
                            ->otherwise(function ($onRejection) use ($providerId, $clientID, $providersAndClients_rejection, $providersAndClients) {
                                Log::info('$onRejection', [
                                    'clientID' => $clientID,
                                    '$providerId' => $providerId,
                                ]);
                                $providersAndClients->push([
                                    'providerID' => $providerId,
                                    'clientID' => $clientID,
                                    'doisCount' => '***'
                                ]);
                                $providersAndClients_rejection->push([
                                    'clientID' => $clientID,
                                    '$providerId' => $providerId,
                                ]);
                            });

                    }, $clients));


            }


            return $providersAndClients;


        } catch (Throwable $exception) {
            return $exception->getMessage();
        }
    }


}
