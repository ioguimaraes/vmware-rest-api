# VMWare Rest API PHP

Copyright 2023 Igor Guimarães

Version 1.0

## Description

Basic class PHP compiling the main vCenter API calls, making it possible to simultaneously manage more than one virtualization environment.

## Important

* For more information, visit the official API link: https://developer.vmware.com/apis/vsphere-automation/latest/vcenter/
* The methods present in the class were added to the vCenter framework after version 7.0 U2
* VMWare Rest API works with PHP 7.4+

## Installation

To install VMWare Rest API PHP, run the folling command:

```
composer require ioguimaraes/vmware-rest-api
```

Installation instructions to use the `composer` command can be found on [https://github.com/composer/composer](https://github.com/composer/composer).

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
* VM Hardware Other Info
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
        "name": "Teste_vm1",
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

### Create VM

Creates a virtual machine. if you do not have all of the privileges described as follows: - The resource Folder referenced by the attribute VM.InventoryPlacementSpec.folder requires VirtualMachine.Inventory.Create.

Below are parameters that can be filled in when creating machines.

```
$params = 
{
    "boot": {
        "delay": 0,
        "efi_legacy_boot": false,
        "enter_setup_mode": false,
        "network_protocol": "IPV4",
        "retry": false,
        "retry_delay": 0,
        "type": "BIOS"
    },
    "boot_devices": [ {
        "type": "CDROM"
    } ],
    "cdroms": [ {
        "allow_guest_control": false,
        "backing": {
            "device_access_type": "EMULATION",
            "host_device": "",
            "iso_file": "",
            "type": "ISO_FILE"
        },
        "ide": {
            "master": false,
            "primary": false
        },
        "sata": {
            "bus": 0,
            "unit": 0
        },
        "start_connected": false,
        "type": "IDE"
    } ],
    "cpu": {
        "cores_per_socket": 0,
        "count": 0,
        "hot_add_enabled": false,
        "hot_remove_enabled": false
    },
    "disks": [ {
        "backing": {
            "type": "VMDK_FILE",
            "vmdk_file": ""
        },
        "ide": {
            "master": false,
            "primary": false
        },
        "new_vmdk": {
            "capacity": 0,
            "name": "",
            "storage_policy": {
                "policy": ""
            }
        },
        "nvme": {
            "bus": 0,
            "unit": 0
        },
        "sata": {
            "bus": 0,
            "unit": 0
        },
        "scsi": {
            "bus": 0,
            "unit": 0
        },
        "type": "IDE"
    } ],
    "floppies": [ {
        "allow_guest_control": false,
        "backing": {
            "host_device": "",
            "image_file": "",
            "type": "IMAGE_FILE"
        },
        "start_connected": false
    } ],
    "guest_OS": "DOS",
    "hardware_version": "VMX_03",
    "memory": {
        "hot_add_enabled": false,
        "size_MiB": 0
    },
    "name": "",
    "nics": [ {
        "allow_guest_control": false,
        "backing": {
            "distributed_port": "",
            "network": "",
            "type": "STANDARD_PORTGROUP"
        },
        "mac_address": "",
        "mac_type": "MANUAL",
        "pci_slot_number": 0,
        "start_connected": false,
        "type": "E1000",
        "upt_compatibility_enabled": false,
        "wake_on_lan_enabled": false
    } ],
    "nvme_adapters": [ {
        "bus": 0,
        "pci_slot_number": 0
    } ],
    "parallel_ports": [ {
        "allow_guest_control": false,
        "backing": {
            "file": "",
            "host_device": "",
            "type": "FILE"
        },
        "start_connected": false
    } ],
    "placement": {
        "cluster": "",
        "datastore": "",
        "folder": "",
        "host": "",
        "resource_pool": ""
    },
    "sata_adapters": [ {
        "bus": 0,
        "pci_slot_number": 0,
        "type": "AHCI"
    } ],
    "scsi_adapters": [ {
        "bus": 0,
        "pci_slot_number": 0,
        "sharing": "NONE",
        "type": "BUSLOGIC"
    } ],
    "serial_ports": [ {
        "allow_guest_control": false,
        "backing": {
            "file": "",
            "host_device": "",
            "network_location": "",
            "no_rx_loss": false,
            "pipe": "",
            "proxy": "",
            "type": "FILE"
        },
        "start_connected": false,
        "yield_on_poll": false
    } ],
    "storage_policy": {
        "policy": ""
    }
};
```

```
$api->setNewVirtualMachine($session_id['data']['value'], $params);
```

Attention Point!! The GuestOS enumerated type defines the valid guest operating system types used for configuring a virtual machine. (https://developer.vmware.com/apis/vsphere-automation/latest/vcenter/api/vcenter/vm/post/)

### Delete VM

Deletes a virtual machine. if you do not have all of the privileges described as follows: - The resource VirtualMachine referenced by the parameter vm requires VirtualMachine.Inventory.Delete.

```
$api->deleteVirtualMachine($session_id['data']['value'], $vms['data']['value'][0]['vm']);
```

### VM Tools

The Tools service provides operations for managing VMware Tools in the guest operating system.

```
$vm_tools = $api->getVmTools($session_id['data']['value'], $vms['data']['value'][0]['vm']);

/* result
{
  "status" => true,
  "data" => {
    "value": {
        "auto_update_supported": true,
        "upgrade_policy": "MANUAL",
        "install_attempt_count": 6,
        "version_status": "CURRENT",
        "version_number": 10361,
        "run_state": "RUNNING",
        "version": "10361",
        "install_type": "TAR"
    }
  }
}
*/
```

### VM Guest

The Customization service provides operations to apply a customization specification to a virtual machine in powered-off status.

```
$vm_tools = $api->getVmGuest($session_id['data']['value'], $vms['data']['value'][0]['vm'], 'identity');

/* result guest identity
{
  "status" => true,
  "data" => {
    "value": {
        "full_name": {
            "args": [],
            "default_message": "SUSE Linux Enterprise 12 (64-bit)",
            "id": "vmsg.guestos.sles12_64Guest.label"
        },
        "name": "SLES_12_64",
        "ip_address": "1.134.252.156",
        "family": "LINUX",
        "host_name": "lnrshr1"
    }
  }
}
*/

------------------------------

$vm_tools = $api->getVmGuest($session_id['data']['value'], $vms['data']['value'][0]['vm'], 'local-filesystem');

/* result guest identity
{
  "status" => true,
  "data" => {
    "value": [ 
        {
            "value": {
                "mappings": [],
                "free_space": 1754636288,
                "capacity": 2077073408
            },
            "key": "/tmp"
        },
        {
            "value": {
                "mappings": [],
                "free_space": 5155688448,
                "capacity": 10464022528
            },
            "key": "/usr"
        },
        {
            "value": {
                "mappings": [],
                "free_space": 821157888,
                "capacity": 1020702720
            },
            "key": "/opt"
        },
    ]
  }
}
*/

------------------------------

$vm_tools = $api->getVmGuest($session_id['data']['value'], $vms['data']['value'][0]['vm'], 'power');

/* result guest identity
{
  "status" => true,
  "data" => {
    "value": {
        "operations_ready": true,
        "state": "RUNNING"
    }
  }
}
*/

```

### VM Hardware Basic

The Hardware service provides operations for configuring the virtual hardware of a virtual machine.

```
$vm_hardware = $api->getVmHardwareBasic($session_id['data']['value'], $vms['data']['value'][0]['vm']);

/* result
{
  "status" => true,
  "data" => {
    "value": {
        "upgrade_policy": "NEVER",
        "upgrade_status": "NONE",
        "version": "VMX_13"
    }
  }
}
*/
```

### VM HArdware Adapter

* The Sata service provides operations for configuring the virtual SATA adapters of a virtual machine.
* The Scsi service provides operations for configuring the virtual SCSI adapters of a virtual machine.

```
$vm_hardware_adapter = $api->getVmHardwareAdapter($session_id['data']['value'], $vms['data']['value'][0]['vm'], 'sata');
```

### VM Hardware Boot

The Boot service provides operations for configuring the settings used when booting a virtual machine.

```
$vm_hadware_boot = $api->getVmHardwareBoot($session_id['data']['value'], $vms['data']['value'][0]['vm']);

/* result
{
  "status" => true,
  "data" => {
    "value": {
        "delay": 5000,
        "retry_delay": 10000,
        "enter_setup_mode": false,
        "type": "BIOS",
        "retry": false
    }
  }
}
*/
```

### VM Hardware Other Info

List of the informations type:

* cdrom - The Cdrom service provides operations for configuring the virtual CD-ROM devices of a virtual machine.
* cpu - The Cpu service provides operations for configuring the CPU settings of a virtual machine.
* disk - The Disk service provides operations for configuring the virtual disks of a virtual machine. A virtual disk has a backing such as a VMDK file.
* ethernet - The Ethernet service provides operations for configuring the virtual Ethernet adapters of a virtual machine.
* floppy - The Floppy service provides operations for configuring the virtual floppy drives of a virtual machine.
* memory - The Memory service provides operations for configuring the memory settings of a virtual machine.
* parallel - The Parallel service provides operations for configuring the virtual parallel ports of a virtual machine.
* serial - The Serial service provides operations for configuring the virtual serial ports of a virtual machine.

```
$vm_hadware_others = $api->getVmHardwareInfo($session_id['data']['value'], $vms['data']['value'][0]['vm'], 'cdrom');

/* result cdrom
{
  "status" => true,
  "data" => {
    "value": [
        {
            "cdrom": "16000"
        }
    ]
  }
}

result cpu
{
  "status" => true,
  "data" => {
    "value": {
        "hot_remove_enabled": false,
        "count": 9,
        "hot_add_enabled": false,
        "cores_per_socket": 3
    }
  }
}

result disk
{
  "status" => true,
  "data" => {
    "value": [
        {
            "disk": "2000"
        },
        {
            "disk": "2001"
        }
    ]
  }
}

result ethernet
{
  "status" => true,
  "data" => {
    "value": [
        {
            "nic": "4000"
        },
        {
            "nic": "4001"
        },
        {
            "nic": "4002"
        }
    ]
  }
}

result memory
{
  "status" => true,
  "data" => {
    "value": {
        "size_MiB": 51200,
        "hot_add_enabled": false
    }
  }
}
*/
```

### VM Power

The Power service provides operations for managing the power state of a virtual machine.

```
$vm_power = $api->getFolders($session_id['data']['value']);

/* result vm power
{
  "status" => true,
  "data" => {
    "value": {
        "state": "POWERED_ON"
    }
  }
}
*/
```

### VM Storage Policy

* The Policy service provides operations to configure the storage policies associated with the virtual machine home and/or its virtual disks.
* The Compliance service provides operations that return the compliance status of virtual machine entities(virtual machine home directory and virtual disks) that specify storage policy requirements.

```
$vm_policy = $api->getVmStoragePolicy($session_id['data']['value'], $vms['data']['value'][0]['vm']);
$vm_policy_compliance = $api->getVmStoragePolicy($session_id['data']['value'], $vms['data']['value'][0]['vm'], true);
```

## Get Folders

The Folder service provides operations for manipulating a vCenter Server folder.

```
$filter_example = ['filter.names.1' => 'Teste'];

$folders = $api->getVmPower($session_id['data']['value'], $vms['data']['value'][0]['vm'], $filter_example);

/* result Folders
{
  "status" => true,
  "data" => {
    "value": [
        {
            "folder": "group-d1",
            "name": "Datacenters",
            "type": "DATACENTER"
        },
        {
            "folder": "group-d3",
            "name": "Brazil",
            "type": "DATACENTER"
        },
    ]
  }
}
*/
```

## vCenter Clusters

The Cluster service provides operations to manage clusters in the vCenter Server.

```
$clusters = $api->getClusters($session_id['data']['value']);

/* result
{
  "status" => true,
  "data" => {
    "value": [
        {
            "drs_enabled": true,
            "cluster": "domain-c14174",
            "name": "Test 1",
            "ha_enabled": true
        },
        {
            "drs_enabled": false,
            "cluster": "domain-c160034",
            "name": "Test 2",
            "ha_enabled": true
        }
    ]
  }
}
*/
```

## vCenter Datacenters

The Datacenter service provides operations to manage datacenters in the vCenter Server.

```
$datasore = $api->getDatastores($session_id['data']['value']);

/* result
{
  "status" => true,
  "data" => {
    "value": [
        {
            "name": "Datacenter Teste",
            "datacenter": "datacenter-1"
        }
    ]
  }
}
*/
```

## vCenter Datastore

The Datastore service provides operations for manipulating a datastore.

```
$datacenters = $api->getDatacenters($session_id['data']['value']);

/* result
{
  "status" => true,
  "data" => {
    "value": [
        {
            "datastore": "datastore-11133",
            "name": "datastore_test 1",
            "type": "VMFS",
            "free_space": 2032225222656,
            "capacity": 3298266447872
        },
        {
            "datastore": "datastore-111510",
            "name": "datastore_test 2",
            "type": "VMFS",
            "free_space": 5658868973568,
            "capacity": 10994847842304
        }
    ]
  }
}
*/
```
