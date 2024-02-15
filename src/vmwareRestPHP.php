<?php

// VMWareAPI version 1.0
// Copyright 2023 Igor Guimarães

namespace vmwareRestPHP;

class vmwareRestPHP
{

    /**
     * The URL to the VMWare Rest API.
     * Ex. "https://190.160.0.5/rest"
     * @var string
     */
    private $base_url;

    /**
     * The Hash to allow access to the API.
     * Using username and password to generate hash in base64_encode().
     * @var string
     */
    private $hash;

    /**
     * Setting security in cURL to the access API
     * @var bool
     */
    private $secure;

    /**
     * Setting proxy to the access virtualization environment
     * @var string
     */
    private $proxy;

    /**
     * Set headers
     * @var array
     */
    private $headers = [];

    /**
     * Standard settings headers need to access Rest API
     * @var array
     */
    private $headers_std = [
        'Accept: */*',
        'Accept-Encoding: gzip, deflate, br',
        'Connection: keep-alive',
        'User-Agent: curl/7.54.0'
    ];

    /**
     * Basic settings to access infos in the vcenter
     * @param String $ipaddress The ipaddress of the vcenter.
     * @param String $username The username with allow access vcenter
     * @param String $password The password with allow access vcenter
     * @param bool $secure Validate this used security flag
     * @param String $proxy A proxy to access the virtual environment with jump-server
     */
    public function __construct(String $ipaddress, String $username, String $password, bool $secure = false, String $proxy = '') {

        $this->base_url = "https://{$ipaddress}/rest";
        $this->hash = base64_encode("$username:$password");
        $this->secure = $secure;
        $this->proxy = $proxy;

    }

    /**********************
     * Internal functions *
     **********************/

    /**
     * Set headers to access API
     * @param String|array|null $opts Set extra opsets to access endpoints
     * @param bool $set_auth Setting basic auth. Using to create session ID.
     * @return String
     */
    private function __setHeaders($opts = null, bool $set_auth = false) : String
    {
        if($set_auth) {
            $this->headers = $this->headers_std;
            $this->headers[] = "Authorization: Basic {$this->hash}";
        }

        if(!empty($opts) && is_array($opts)) {
            foreach ($opts as $opt) {
                $this->headers[] = $opt;
            }
        } else {
            $this->headers[] = $opts;
        }

        return "Updated headers with auth and opt info ...";
    }

    /**
     * Function responsible for calling endpoints and processing the return
     * @param String $endpoint_uri Set endpoint in API to the access is required
     * @param String $request_type Define the call request type
     * @param array $parameters Set others parameters wanted to the correct return API
     * @return array
     */
    private function __sendRequest(String $endpoint_uri, String $request_type = 'GET', array $parameters = []) : array
    {

        try {

            $ch = curl_init();
            $url = $this->base_url . $endpoint_uri;
            curl_setopt($ch, CURLOPT_POST, $request_type !== 'GET');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request_type);
            curl_setopt($ch, CURLOPT_ENCODING, "");
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            curl_setopt($ch, CURLOPT_FAILONERROR,false);
            curl_setopt($ch, CURLOPT_VERBOSE, false);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60); //timeout in seconds
            curl_setopt($ch, CURLOPT_HTTPHEADER, array_filter($this->headers));

            if(!$this->secure){
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            }

            if(!empty($parameters)){
                if($request_type == 'GET') {
                    $url .= sprintf("?%s", http_build_query($parameters));
                } else {
                    $payload = json_encode($parameters, JSON_NUMERIC_CHECK);
                    $this->headers[] = 'Content-Type: application/json';

                    if($request_type !== 'PUT') $this->headers[] = 'Content-Length: ' . strlen($payload);

                    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                }
            }

            curl_setopt($ch, CURLOPT_URL, $url);
            $response = json_decode(curl_exec($ch),true);
            curl_close($ch);

            if(isset($response['type']) && $response['type'] === 'com.vmware.vapi.std.errors.unauthenticated') return ['status' => false,  'data' => $response];
            else return ['status' => true,  'data' => $response];

        } catch(\Exception $e) {
            return ["status" => false, "code" => $e->getCode(), "data" => $e->getMessage(), "trace" => $e->getTraceAsString(), "timestamp" => date("Y-m-d H:i:s")];
        }  catch (\Throwable $e) {
            return ["status" => false, "code" => $e->getCode(), "data" => $e->getMessage(), "trace" => $e->getTraceAsString(), "timestamp" => date("Y-m-d H:i:s")];
        }

    }


    /*********************
     * Session Functions *
     *********************/
    /**
     * Creates the session ID that allows access to the environments.
     * It is mandatory to use it in version 7.4.u or higher of vsphere for the API to validate access.
     * ID is valid to 5 minutes.
     * @param array $headers Set other headers infos
     * @return array
     */
    public function vmwareCreateSessionId(array $headers = []) :array {
        try{
            $this->__setHeaders($headers, true);
            return $this->__sendRequest("/com/vmware/cis/session",'POST');
        }catch(\Exception $e){
            return ['status' => false, 'data' => $e->getMessage()];
        }catch(\Throwable $err){
            return ['status' => false, 'data' => $err->getMessage()];
        }
    }

    /**
     * Removes the created session id from the list of valid ids for access.
     * For security, vsphere only allows you to create 50 concurrent sessions per instance by default.
     * @param String $sessionId Previously created session id
     * @return array
     */
    public function vmwareDeleteSessionId(String $sessionId) :array {
        try{
            $this->__setHeaders("vmware-api-session-id: $sessionId");
            return $this->__sendRequest("/com/vmware/cis/session",'DELETE');
        }catch(\Exception $e){
            return ['status' => false, 'data' => $e->getMessage()];
        }catch(\Throwable $err){
            return ['status' => false, 'data' => $err->getMessage()];
        }
    }


    /****************************************
     * Public functions to virtual machines *
     ****************************************/
    /**
     * List the basic information of the VMs that are in vcenter.
     * @param String $sessionId Previously created session id
     * @param array $params Add filters to the search vms
     * @param String|null $vmId Virtual machine identifier
     * @return array
     */
    public function getVirtualMachines(String $sessionId, array $params = [], String $vmId = null) : array {
        try{
            $this->__setHeaders("vmware-api-session-id: $sessionId");
            $endpoint = is_null($vmId) ? "/vcenter/vm" : "/vcenter/vm/$vmId";
            return $this->__sendRequest($endpoint,'GET', $params);
        }catch(\Exception $e){
            return ['status' => false, 'data' => $e->getMessage()];
        }catch(\Throwable $err){
            return ['status' => false, 'data' => $err->getMessage()];
        }
    }

    /**
     * Creates a virtual machine.
     * If you do not have all of the privileges described as follows: - The resource Folder referenced by the attribute VM.InventoryPlacementSpec.folder requires VirtualMachine.Inventory.Create.
     * @param String $sessionId Previously created session id
     * @param array $params
     * @return array
     */
    public function setNewVirtualMachine(String $sessionId, array $params = []) : array {
        try{
            $this->__setHeaders("vmware-api-session-id: $sessionId");
            return $this->__sendRequest("/vcenter/vm",'POST', $params);
        }catch(\Exception $e){
            return ['status' => false, 'data' => $e->getMessage()];
        }catch(\Throwable $err){
            return ['status' => false, 'data' => $err->getMessage()];
        }
    }

    /**
     * Deletes a virtual machine.
     * If you do not have all of the privileges described as follows: - The resource VirtualMachine referenced by the parameter vm requires VirtualMachine.Inventory.Delete.
     * @param String $sessionId Previously created session id
     * @param String $vmId Virtual machine identifier
     * @return array
     */
    public function deleteVirtualMachine(String $sessionId, String $vmId) : array {
        try{
            $this->__setHeaders("vmware-api-session-id: $sessionId");
            return $this->__sendRequest("/vcenter/vm/$vmId",'DELETE');
        }catch(\Exception $e){
            return ['status' => false, 'data' => $e->getMessage()];
        }catch(\Throwable $err){
            return ['status' => false, 'data' => $err->getMessage()];
        }
    }

    /**
     * The Tools service provides operations for managing VMware Tools in the guest operating system.
     * @param String $sessionId Previously created session id
     * @param String $vmId Virtual machine identifier
     * @return array
     */
    public function getVmTools(String $sessionId, String $vmId) : array{
        try {
            $this->__setHeaders("vmware-api-session-id: $sessionId");
            return $this->__sendRequest("/vcenter/vm/$vmId/tools",'GET');
        }catch(\Exception $e){
            return ['status' => false, 'data' => $e->getMessage()];
        }catch(\Throwable $err){
            return ['status' => false, 'data' => $err->getMessage()];
        }
    }

    /**
     * The Customization service provides operations to apply a customization specification to a virtual machine in powered-off status.
     * @param String $sessionId Previously created session id
     * @param String $vmId Virtual machine identifier
     * @param String|null $info Defines the module that will pull the information
     * @return array
     */
    public function getVmGuest(String $sessionId, String $vmId, String $info = null) : array {
        try{

            $validate = ['identity', 'local-filesystem', 'power'];

            if(in_array($info, $validate)) {
                $this->__setHeaders("vmware-api-session-id: $sessionId");
                return $this->__sendRequest("/vcenter/vm/$vmId/guest/$info",'GET');
            } else {
                return ['status' => false, 'data' => "Invalid request. Valid Options: " . json_encode($validate)];
            }
        }catch(\Exception $e){
            return ['status' => false, 'data' => $e->getMessage()];
        }catch(\Throwable $err){
            return ['status' => false, 'data' => $err->getMessage()];
        }
    }

    /**
     * Returns the virtual hardware settings of a virtual machine.
     * @param String $sessionId Previously created session id
     * @param String $vmId Virtual machine identifier
     * @return array
     */
    public function getVmHardwareBasic(String $sessionId, String $vmId) : array {
        try{
            $this->__setHeaders("vmware-api-session-id: $sessionId");
            return $this->__sendRequest("/vcenter/vm/$vmId/hardware",'GET');
        }catch(\Exception $e){
            return ['status' => false, 'data' => $e->getMessage()];
        }catch(\Error $err){
            return ['status' => false, 'data' => $err->getMessage()];
        }
    }

    /**
     * The Sata service provides operations for configuring the virtual SATA adapters of a virtual machine.
     * The Scsi service provides operations for configuring the virtual SCSI adapters of a virtual machine.
     * @param String $sessionId Previously created session id
     * @param String $vmId Virtual machine identifier
     * @param String|null $type Adapter type
     * @param String|null $adapterId Adapter Identifier
     * @return array
     */
    public function getVmHardwareAdapter(String $sessionId, String $vmId, String $type = null, String $adapterId = null) : array {
        try{
            $validate = ['sata', 'scsi'];

            if(in_array($type, $validate)) {
                $this->__setHeaders("vmware-api-session-id: $sessionId");
                $endpoint = is_null($adapterId) ? "/vcenter/vm/$vmId/hardware/$type" : "/vcenter/vm/$vmId/hardware/$type/$adapterId";
                return $this->__sendRequest($endpoint,'GET');
            } else {
                return ['status' => false, 'data' => "Invalid request. Valid Options: " . json_encode($validate)];
            }
        }catch(\Exception $e){
            return ['status' => false, 'data' => $e->getMessage()];
        }catch(\Error $err){
            return ['status' => false, 'data' => $err->getMessage()];
        }
    }

    /**
     * The Boot service provides operations for configuring the settings used when booting a virtual machine.
     * @param String $sessionId Previously created session id
     * @param String $vmId Virtual machine identifier
     * @param bool $device Details device info
     * @return array
     */
    public function getVmHardwareBoot(String $sessionId, String $vmId, bool $device = false) : array {
        try{
            $this->__setHeaders("vmware-api-session-id: $sessionId");
            $endpoint = $device ? "/vcenter/vm/$vmId/hardware/boot" : "/vcenter/vm/$vmId/hardware/boot/device";
            return $this->__sendRequest($endpoint,'GET');
        }catch(\Exception $e){
            return ['status' => false, 'data' => $e->getMessage()];
        }catch(\Error $err){
            return ['status' => false, 'data' => $err->getMessage()];
        }
    }

    /**
     * @param String $sessionId Previously created session id
     * @param String $vmId Virtual machine identifier
     * @param String|null $type Filter type hardware info
     * @param String|null $Id Identify module
     * @return array
     */
    public function getVmHardwareInfo(String $sessionId, String $vmId, String $type = null, String $Id = null) : array {
        try{
            $validate = ['cdrom', 'cpu', 'disk', 'ethernet', 'floppy', 'memory', 'parallel', 'serial'];

            if(in_array($type, $validate)) {
                $this->__setHeaders("vmware-api-session-id: $sessionId");
                $endpoint = is_null($Id) ? "/vcenter/vm/$vmId/hardware/$type" : "/vcenter/vm/$vmId/hardware/$type/$Id";
                return $this->__sendRequest($endpoint,'GET');
            } else {
                return ['status' => false, 'data' => "Invalid request. Valid Options: " . json_encode($validate)];
            }
        }catch(\Exception $e){
            return ['status' => false, 'data' => $e->getMessage()];
        }catch(\Error $err){
            return ['status' => false, 'data' => $err->getMessage()];
        }
    }

    /**
     * The Power service provides operations for managing the power state of a virtual machine.
     * @param String $sessionId Previously created session id
     * @param String $vmId Virtual machine identifier
     * @return array
     */
    public function getVmPower(String $sessionId, String $vmId) : array {
        try{
            $this->__setHeaders("vmware-api-session-id: $sessionId");
            $endpoint = "/vcenter/vm/$vmId/power";
            return $this->__sendRequest($endpoint,'GET');
        }catch(\Exception $e){
            return ['status' => false, 'data' => $e->getMessage()];
        }catch(\Error $err){
            return ['status' => false, 'data' => $err->getMessage()];
        }
    }

    /**
     * The Policy service provides operations to configure the storage policies associated with the virtual machine home and/or its virtual disks.
     * The Compliance service provides operations that return the compliance status of virtual machine entities(virtual machine home directory and virtual disks) that specify storage policy requirements.
     * @param String $sessionId Previously created session id
     * @param String $vmId Virtual machine identifier
     * @param bool $compliance Define view policy with compliance mode active
     * @return array
     */
    public function getVmStoragePolicy(String $sessionId, String $vmId, bool $compliance = false) : array {
        try{
            $this->__setHeaders("vmware-api-session-id: $sessionId");
            $endpoint = $compliance ? "/vcenter/vm/$vmId/storage/policy/compliance" : "/vcenter/vm/$vmId/storage/policy";
            return $this->__sendRequest($endpoint,'GET');
        }catch(\Exception $e){
            return ['status' => false, 'data' => $e->getMessage()];
        }catch(\Error $err){
            return ['status' => false, 'data' => $err->getMessage()];
        }
    }


    /**
     * The Folder service provides operations for manipulating a vCenter Server folder.
     * @param String $sessionId Previously created session id
     * @param array $params Filters
     * @return array
     */
    public function getFolders(String $sessionId, array $params = []) : array {
        try{
            $this->__setHeaders("vmware-api-session-id: $sessionId");
            return $this->__sendRequest("/vcenter/folder",'GET', $params);
        }catch(\Exception $e){
            return ['status' => false, 'data' => $e->getMessage()];
        }catch(\Throwable $err){
            return ['status' => false, 'data' => $err->getMessage()];
        }
    }

    /**
     * The Cluster service provides operations to manage clusters in the vCenter Server.
     * @param String $sessionId Previously created session id
     * @param String|null $clusterId Cluster Identify
     * @return array
     */
    public function getClusters(String $sessionId, String $clusterId = null) : array {
        try{
            $this->__setHeaders("vmware-api-session-id: $sessionId");
            $endpoint = is_null($clusterId) ? "/vcenter/cluster" : "/vcenter/cluster/$clusterId";
            return $this->__sendRequest($endpoint,'GET');
        }catch(\Exception $e){
            return ['status' => false, 'data' => $e->getMessage()];
        }catch(\Error $err){
            return ['status' => false, 'data' => $err->getMessage()];
        }
    }

    /**
     * The Datacenter service provides operations to manage datacenters in the vCenter Server.
     * @param String $sessionId Previously created session id
     * @param String|null $datacenterId Identify
     * @return array
     */
    public function getDatacenters(String $sessionId, String $datacenterId = null) : array {
        try{
            $this->__setHeaders("vmware-api-session-id: $sessionId");
            $endpoint = is_null($datacenterId) ? "/vcenter/datacenter" : "/vcenter/datacenter/$datacenterId";
            return $this->__sendRequest($endpoint,'GET');
        }catch(\Exception $e){
            return ['status' => false, 'data' => $e->getMessage()];
        }catch(\Error $err){
            return ['status' => false, 'data' => $err->getMessage()];
        }
    }

    /**
     * The Datastore service provides operations for manipulating a datastore.
     * @param String $sessionId Previously created session id
     * @param String|null $datastoreId Identify
     * @return array
     */
    public function getDatastores(String $sessionId, String $datastoreId = null) : array {
        try{
            $this->__setHeaders("vmware-api-session-id: $sessionId");
            $endpoint = is_null($datastoreId) ? "/vcenter/datastore" : "/vcenter/datastore/$datastoreId";
            return $this->__sendRequest($endpoint,'GET');
        }catch(\Exception $e){
            return ['status' => false, 'data' => $e->getMessage()];
        }catch(\Error $err){
            return ['status' => false, 'data' => $err->getMessage()];
        }
    }
}
