# VMWare Rest API PHP

Copyright 2023 Igor Guimarães

Version 1.0

## Description

Basic class PHP compiling the main vCenter API calls, making it possible to simultaneously manage more than one virtualization environment.

## Important

* For more information, visit the official API link: https://developer.vmware.com/apis/vsphere-automation/latest/vcenter/
* The methods present in the class were added to the vCenter framework after version 7.0 U2

## Authentication

The vCenter API supports the following methods to authenticate requests. Individual operations in the documentation will include their specific authentication types.

* api_key
  * Type: APIKey
  * In: Header
  * Name: vmware-api-session-id
* basic_auth
  * Type: HTTP
  * In: Header
  * Scheme: Basic

**Attention Point!!** the API key is only valid for 5 minutes and by default and vCenter allows the simultaneous creation of a maximum of 50 keys per environment, of which it is advisable that after finishing using the key, destroy it of the same before creating a new one.

#### Basic Auth Ex ...

```
<?php
include_once  "..\src\vmwareRestPHP.php";

$ipaddress = '160.190.0.35';
$username = 'teste';
$password = 'teste1234';

$api = new vmwareRestApi($ipaddress, $username, $password);
$session_id = $api->vmwareCreateSessionId();

print_r($session_id);

```

return:

```
{
    "status" => true,
    "data" => {
        "value" => "9a868d715ad5411a39b75f5fef482e9b"
    }
}
```

#### Destroy Session

```
$api->vmwareDeleteSessionId(api->vmwareDeleteSessionId($session_id);
```

### Access with NGINX

If it is necessary to access an environment through a reverse proxy using NGINX, the class allows the reverse proxy access configuration to be carried out.

```
$reverse_proxy = '1.10.0.3:9008';
$nginx_username = 'teste_nginx';
$nginx_password = 'mginx_teste1234';
$username = 'teste';
$password = 'teste1234';

$api = new vmwareRestApi($reverse_proxy, $nginx_username, $nginx_password);
$session_id = $api->vmwareCreateSessionId(["vmware-auth: Basic " . base64_encode("{$username}:{$password}")]);
```

**Attention Point!!** The 'vmware-auth' variable must be configured with the same name within NGINX so that when it directs to the correct IP, this new auth is assigned. Otherwise, the reverse proxy Auth will be sent, which for security reasons must be different from that used by the VMWare environment.

## Virtual Machines

The vcenter vm package provides services for managing virtual machines.

* VM List
* VM Details
* Create VM
* Delete VM
* VM Tools
* VM Guest
* VM Hardware Basic
* VM HArdware Adapter
* VM Hardware Boot
* VM Hardware Info
* VM Power
* VM Storage Policy

### VM List & Details

Returns information about at most 4000 visible (subject to permission checks) virtual machines in vCenter matching the VM.FilterSpec.

```
//List All vms
$vms = $api->getVirtualMachines($session_id['data']['value']);

/* return info
{
    "status" => true,
    "data" => {
        "value" => {
            {
                "memory_size_MiB": 51200,
                "vm": "vm-105875",
                "name": "Teste_vm1",
                "power_state": "POWERED_ON",
                "cpu_count": 9
            },
            {
                "memory_size_MiB": 221184,
                "vm": "vm-108158",
                "name": "Teste_vm2",
                "power_state": "POWERED_OFF",
                "cpu_count": 20
            },
        }
    }
}
*/


//VM detail
$vm = $api->getVirtualMachines($session_id['data']['value'], [], $vms['data']['value'][0]['vm']);

/* return info
{
    "status" => true,
    "data" => {
    "value": {
        "instant_clone_frozen": false,
        "cdroms": [
            {
                "value": {
                    "start_connected": false,
                    "backing": {
                        "device_access_type": "EMULATION",
                        "type": "CLIENT_DEVICE"
                    },
                    "allow_guest_control": true,
                    "label": "CD/DVD drive 1",
                    "state": "NOT_CONNECTED",
                    "type": "SATA",
                    "sata": {
                        "bus": 0,
                        "unit": 0
                    }
                },
                "key": "16000"
            }
        ],
        "memory": {
            "hot_add_increment_size_MiB": 0,
            "size_MiB": 51200,
            "hot_add_enabled": false,
            "hot_add_limit_MiB": 51200
        },
        "disks": [
            {
                "value": {
                    "scsi": {
                        "bus": 0,
                        "unit": 0
                    },
                    "backing": {
                        "vmdk_file": "[Teste] Teste_vm1/Teste_vm1.vmdk",
                        "type": "VMDK_FILE"
                    },
                    "label": "Hard disk 1",
                    "type": "SCSI",
                    "capacity": 107374182400
                },
                "key": "2000"
            },
            {
                "value": {
                    "scsi": {
                        "bus": 0,
                        "unit": 1
                    },
                    "backing": {
                        "vmdk_file": "[Teste] Teste_vm1/Teste_vm1_10.vmdk",
                        "type": "VMDK_FILE"
                    },
                    "label": "Hard disk 2",
                    "type": "SCSI",
                    "capacity": 214748364800
                },
                "key": "2001"
            }
        ],
        "parallel_ports": [],
        "sata_adapters": [
            {
                "value": {
                    "bus": 0,
                    "label": "SATA controller 0",
                    "type": "AHCI"
                },
                "key": "15000"
            }
        ],
        "cpu": {
            "hot_remove_enabled": false,
            "count": 9,
            "hot_add_enabled": false,
            "cores_per_socket": 3
        },
        "scsi_adapters": [
            {
                "value": {
                    "scsi": {
                        "bus": 0,
                        "unit": 7
                    },
                    "label": "SCSI controller 0",
                    "sharing": "NONE",
                    "type": "PVSCSI"
                },
                "key": "1000"
            },
            {
                "value": {
                    "pci_slot_number": 1184,
                    "scsi": {
                        "bus": 1,
                        "unit": 7
                    },
                    "label": "SCSI controller 1",
                    "sharing": "NONE",
                    "type": "PVSCSI"
                },
                "key": "1001"
            }
        ],
        "power_state": "POWERED_ON",
        "floppies": [],
        "identity": {
            "name": "Teste_vm1",
            "instance_uuid": "5028198f-5f09-7c36-5956-16e1ac99641c",
            "bios_uuid": "4228c71f-4d7d-967f-0831-b4180e5b207c"
        },
        "nvme_adapters": [],
        "name": "ap3std0518",
        "nics": [
            {
                "value": {
                    "start_connected": true,
                    "pci_slot_number": 192,
                    "backing": {
                        "connection_cookie": 74116667,
                        "distributed_port": "3542",
                        "distributed_switch_uuid": "50 28 b4 87 38 cf 82 26-b8 56 4e 43 53 65 a6 9c",
                        "type": "DISTRIBUTED_PORTGROUP",
                        "network": "dvportgroup-10482"
                    },
                    "mac_address": "00:50:56:a8:85:2a",
                    "mac_type": "ASSIGNED",
                    "allow_guest_control": true,
                    "wake_on_lan_enabled": true,
                    "label": "Network adapter 1",
                    "state": "CONNECTED",
                    "type": "VMXNET3",
                    "upt_compatibility_enabled": true
                },
                "key": "4000"
            },
            {
                "value": {
                    "start_connected": true,
                    "pci_slot_number": 224,
                    "backing": {
                        "connection_cookie": 74123494,
                        "distributed_port": "2438",
                        "distributed_switch_uuid": "50 28 b4 87 38 cf 82 26-b8 56 4e 43 53 65 a6 9c",
                        "type": "DISTRIBUTED_PORTGROUP",
                        "network": "dvportgroup-10483"
                    },
                    "mac_address": "00:50:56:a8:42:20",
                    "mac_type": "ASSIGNED",
                    "allow_guest_control": true,
                    "wake_on_lan_enabled": true,
                    "label": "Network adapter 2",
                    "state": "CONNECTED",
                    "type": "VMXNET3",
                    "upt_compatibility_enabled": true
                },
                "key": "4001"
            },
            {
                "value": {
                    "start_connected": true,
                    "pci_slot_number": 256,
                    "backing": {
                        "connection_cookie": 74128857,
                        "distributed_port": "232",
                        "distributed_switch_uuid": "50 28 b4 87 38 cf 82 26-b8 56 4e 43 53 65 a6 9c",
                        "type": "DISTRIBUTED_PORTGROUP",
                        "network": "dvportgroup-65"
                    },
                    "mac_address": "00:50:56:a8:1b:47",
                    "mac_type": "ASSIGNED",
                    "allow_guest_control": true,
                    "wake_on_lan_enabled": true,
                    "label": "Network adapter 3",
                    "state": "CONNECTED",
                    "type": "VMXNET3",
                    "upt_compatibility_enabled": true
                },
                "key": "4002"
            }
        ],
        "boot": {
            "delay": 5000,
            "retry_delay": 10000,
            "enter_setup_mode": false,
            "type": "BIOS",
            "retry": false
        },
        "serial_ports": [],
        "boot_devices": [],
        "guest_OS": "SLES_11_64",
        "hardware": {
            "upgrade_policy": "NEVER",
            "upgrade_status": "NONE",
            "version": "VMX_13"
        }
    }
}
}
*/


```
