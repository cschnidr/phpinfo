# PHP Sample Application

This is a very simple PHP application to showcase Openshift and persistent storage. I use it to show the speed and simpleness of a source to image process and how an modern application can scale.

### Demo preparation

The suggested installation is running on a notebook. A linux VM and a NetApp ONTAP simulator is required.
---------------------------------
Prep of Linux VM to run Openshift
---------------------------------
 Linux VM can be downloaded here: http://www.osboxes.org/centos/
 Login with root
 '''
 > yum update -y
 > yum install wget -y
 > yum install nfs-utils -y
 > curl -fsSL https://get.docker.com/ | sh
 Add insecure flag:
 > vi /etc/docker/daemon.json ->
     {
       "insecure-registries" : ["172.30.0.0/16"]
     }
 > systemctl stop firewalld
 > systemctl start docker
 '''
 Test if Docker is working
 # docker version
 Install the OpenShift console tool:
 # wget https://github.com/openshift/origin/releases/download/v3.6.1/openshift-origin-client-tools-v3.6.1-008f2d5-linux-64bit.tar.gz
# gunzip openshift-origin-client-tools-v3.6.1-008f2d5-linux-64bit.tar.gz
# tar xvf openshift-origin-client-tools-v3.6.1-008f2d5-linux-64bit.tar
# mv openshift-origin-client-tools-v3.6.1-008f2d5-linux-64bit/oc /usr/local/bin
Start the OpenShift Cluster
# ip addr
# oc cluster up --public-hostname='192.168.123.180' (IP Address from the ip addr command )
# oc login -u system:admin
# oc adm policy add-cluster-role-to-user cluster-admin admin

---------------------------------
Prep of NetApp ONTAP Sim
---------------------------------
See: https://mysupport.netapp.com/tools/info/ECMLP2538456I.html?platformID=60730&productID=61970&pcfContentID=ECMLP2538456

The sim can be upgrade with the regular ONTAP image you download from mysupport.netapp.com
The process to upgrade the sim is:
The root volume might be to small to host an upgrade:
# storage aggregate add-disks -aggregate aggr0 -diskcount 2
# volume size -vserver sim1-01 -volume vol0 +2g

# system image get -package http://172.16.51.1/image.tgz (--> there is a Chrome plugin to run a webserver on your notebook https://chrome.google.com/webstore/detail/web-server-for-chrome/ofhbbkphhbklhfoeikjpcbhemlocgigb?hl=en)
# system image update -package image.tgz
# system image show
# system image modify -node sim1-01 image2 -isdefault true
# Reboot

Prep the simulator as following:
- Add Licenses
- Create a SVM with NFS protocol v4.0
- Set Aggr flag for API access
- Adjust Default Export Policy for everyone or your private Network
- Security style needs to be unix!
--> test a mount in your Linux VM
If you wanna see the exports (showmount -e) from your Linux VM, enable showmount in your SVM:
cluster ::> nfs server modify -vserver NFS83 -showmount enabled


---------------------------------
Install Trident in your Linux VM
---------------------------------
Download the NetApp Trident software:
# wget https://github.com/NetApp/trident/releases/download/v17.10.0/trident-installer-17.10.0.tar.gz
# gunzip trident-installer-17.10.0.tar.gz
# tar xvf trident-installer-17.10.0.tar
# cd trident-installer
# cp sample-input/backend-ontap-nas.json setup/backend.json
               
Edit the setup/backend.json with the SIM values
# vi setup/backend.json ->
  {
    "version": 1,
    "storageDriverName": "ontap-nas",
    "managementLIF": "172.16.152.20",
    "dataLIF": "172.16.152.21",
    "svm": "openshift",
    "username": "vsadmin",
    "password": "netapp11"
   }            
   # cp sample-input/pvc-basic.yaml setup/pvc-basic.yaml
   # cp sample-input/storage-class-basic-v1.yaml.templ setup/storage-class-basic.yaml
              
   Add backendType to setup/storage-class-basic.yaml
   # vi setup/storage-class-basic.yaml
       apiVersion: storage.k8s.io/v1
       kind: StorageClass
       metadata:
       name: basic
       provisioner: netapp.io/trident
       parameters:
       backendType: "ontap-nas"

# oc new-project trident
# ./install_trident.sh
# oc create -f setup/storage-class-basic.yaml
#./tridentctl create backend -f setup/backend.json
# oc annotate storageclass/basic storageclass.kubernetes.io/is-default-class=true


### demo

```
Add to project -> show all available builders
choose php builder, e.g. 7.0
name example, git https://github.com/cschnidr/phpinfo/, create without options
// --> only works if github can access your installation... copy webhook url, click on github link, add webhook url to github, choose JSON as format !
go back to console tab, go to overview, check build log if not ready, back to overview
show generated URL on top right, click, show running app, back to console
easy to scale up, scale to 2 and 3 pods, mouse hover on the "starting" pods
easy to scale down, go back to 1 pod
add encryption: menu application -> routes -> click on name -> actions -> edit -> show options for ... -> TLS: Edge, Insecure: Redirect
overview: URL changed to https, click and show green lock in browser URL
scale back up to 3
go to github, click index.php, edit, edit echo message at top, commit
console: show build, show rolling upgrade, click on link, show new message
```

Create persistent storage in your app
Mount it to: /opt/app-root/src/web
Go to the terminal inside your container and put the pic in the PV:
wget http://netapp.io/wp-content/uploads/2017/03/thePub_GreyBlue.png

Scale the app, open in different browsers, edit the code in github and redeploy



### cleanup
```
oc delete all -l app=example
```

