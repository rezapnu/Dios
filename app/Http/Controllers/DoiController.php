<?php

namespace App\Http\Controllers;

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

            $i = 0;

            foreach ($providers as $provider) {
                $providerId = $provider['id'];
                $clients = $provider['relationships']['clients']['data'];
                $clientIDs = collect([]);
                $promises = [];

                Log::info('$i', [
                    'i' => $i
                ]);
                $i++;

                foreach ($clients as $client) {
                    $clientID = $client['id'];
                    Log::info('.', [
                        '$clientID' => $clientID,
                        '$providerId' => $providerId,
                        '$clients' => $clients,
                        '$providersAndClients' => $providersAndClients
                    ]);
                    $promises[] = Http::async()->get("https://api.datacite.org/dois?client-id=$clientID")
                        ->then(function ($res) use ($providersAndClients, $providerId, $clientID) {
                            Log::info('.', [
                                'clientID' => $clientID
                            ]);
                            $dois = $res->json('data');
                            $doisCount = Count($dois);
                            $providersAndClients->push([
                                'providerID' => $providerId,
                                'clientID' => $clientID,
                                'doisCount' => $doisCount
                            ]);


                        });
                }
                $responses = Utils::unwrap($promises);
            }

            return $providersAndClients;


        } catch (Throwable $exception) {
            return $exception->getMessage();
        }
    }

    protected function getDoisByClientId($providers)
    {
        try {
            $providersAndClients = collect([]);

            $i = 0;

            foreach ($providers as $provider) {
                $providerId = $provider['id'];
                $clients = $provider['relationships']['clients']['data'];
                $clientIDs = collect([]);
                $promises = [];

                Log::info('$i', [
                    'i' => $i
                ]);
                $i++;

                foreach ($clients as $client) {
                    $clientID = $client['id'];
                    Log::info('.', [
                        '$clientID' => $clientID,
                        '$providerId' => $providerId,
                        '$clients' => $clients,
                        '$providersAndClients' => $providersAndClients
                    ]);
                    $dois = Http::get("https://api.datacite.org/dois?client-id=$clientID")->json('data');
                    $doisCount = Count($dois);
                    $providersAndClients->push([
                        'providerID' => $providerId,
                        'clientID' => $clientID,
                        'doisCount' => $doisCount
                    ]);
                }
            }

            return $providersAndClients;


        } catch (Throwable $exception) {
            return $exception->getMessage();
        }
    }

}
