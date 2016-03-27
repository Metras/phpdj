# VMWare Development Image #

A VMWare image has been prepared to aid in development. It contains everything you need to get both G3 (phpdj - this project) and G2 (pydj - the previous incarnation) running side by side.

The G2 source is current as of 2011-07-11, from the Git repo on the gbsfm server.

The G3 source code is not in the image, it needs to be checked out of the Google Code Repo

[Download the image](http://gbs.fm/dev/vm.gbsfm.dev.7z) you will need 7zip 9.x to extract it.

Check the Downloads for any updated files (the VM image is massive, I won't be updating it regularly). The image was created 2011-08-09

## How to use VMWare ##

If you are not familiar with VMWare, follow these instructions which are for the free VMWare Player. Note that VMWare Workstation is preferred because it is faster and has snapshotting, but it isn't free.

  1. Download [VMWare Player](http://www.vmware.com/products/player/)
  1. In VMWare Player, go to Open and locate the g2.vmx file.
  1. Now click on Play
  1. It will ask if this image was "moved" or "copied". say moved.
  1. If you are using Workstation, take a snapshot at the point for your baseline.
  1. It will boot up. Click in the window to give it control. Tap ctrl + alt to get it back.

## Host Setup ##

You need to add a few DNS entries on your host, you can do this by editing your host file:

```
vm.gbsfm.dev
g2.gbsfm.dev
g3.gbsfm.dev
```

Set them all to the IP that the image is assigned when it boots.

## Guest Linux Image Info ##

We are using debian 5 stable (lenny), fully updated as of 2011-08-09

The machine is called vm.gbsfm.dev

It is set to use DHCP, so it should acquire an IP address from your DHCP server.

We are doing everything as root. The object of this image is to get both systems running, not be secure.

```
Username: root
Password: password
```

There is also a user which we don’t use at all, but here is the login anyway

```
Username: user
Password: password1
```

SSH is installed too.

**Mysql**
```
Username: root
Password: password
```

Database for pydj is gbsfm

Shoutcast DNAS 2 build 29
Shoutcast Transcoder build 51

## phpdj/G3 ##

Set your git global options
```
git config --global user.email="youremailaddress"
git config --global user.name="yourname"
```

Set up your googlecode password on the Source / Checkout page of Google Code and get the repo url then
```
cd /home/g3/site
git clone urlasgiven .
```
that final . is important.

Change a few dir permissions
```
chmod 777 application/logs
chmod 777 application/cache
```

Configuration files are located in /home/g3/site/application/config/
The vm dir contains config files specific for this development image, you shouldn't need to change anything.
The gbsfm dir should be empty because this is where the production config files go and we don't want them in this public repo!

Now start the DNAS ans Transcoder
```
cd /home/g3
. ./restart.sh
```
(that first . is needed)

You should now be able to listen on http://vm.gbsfm.dev:8001/g3
and access the website on http://g3.gbsfm.dev (You should get a hello world)

Bear in mind that there are bugs in the dnas server and transcoder that make them crash on weird tags, so you may be better off starting the transcoder and dnas individually in separate ssh sessions to get the debug output.

The DNAS and Transcoders are in the image but they may be out of date now you are reading this.... (update instructions to be added later)

## pydj/G2 ##

### Setup ###
Edit /home/pydj/settings.py and set the correct IP in the vars:
```
INTERNAL_IPS
STREAMINFO_URL
```

Edit `SA_*` variables for your own SA username and password, this is used for the authentication check when users register.

### Start ###
```
cd /home/pydj
. ./restart.sh
```

The script does 3 things:
  1. Start the Django fastcgi server
  1. Start the Shoutcast DNAS
  1. Start ices

The FTP server listens on port 2100, you can start with
```
  cd /home/pydj/playlist
  python ftp.py
```

### Logging in ###
Now everything should be running. Go to http://g2.gbsfm.dev and log in.

There are two users that have been created:

**Django administrator**
```
Username: root
Password: password
```

This is NOT a pydj user, it’s a django user. It can log in to /admin only, not pydj. You shouldn't need to use this.

**PYDJ User**
```
Username: user
Password: password
```

This is the one to use for pydj (and /admin). It's a superuser. Log in with this and use it.

You should see a few songs in the playlist and one should be playing.

Connect to http://vm.gbsfm.dev:8001/ with your media player and you should get music.

G2 is working!