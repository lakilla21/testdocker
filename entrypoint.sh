#!/bin/bash
cd /home/container

# Make internal Docker IP address available to processes.
export INTERNAL_IP=`ip route get 1 | awk '{print $NF;exit}'`

# Update Server
if [ ! -z ${SRCDS_APPID} ]; then
  ./steam/steamcmd.sh +@sSteamCmdForcePlatformType windows +login anonymous +force_install_dir /home/container +app_update ${SRCDS_APPID} +quit
fi

#Mod updates
cleanmodids=$(echo ${MODIDS} | tr -d ' ')
updatelist=""
if [ ! -z $cleanmodids ]; then
  #Conan Exiles
  if [[ ${SRCDS_APPID} == "443030" ]]; then
    printf "Updating Conan Exiles mods\n"
    for i in $(echo $cleanmodids | sed "s/,/ /g")
    do
      updatelist="$updatelist +workshop_download_item 440900 $i validate"
    done
    ./steam/steamcmd.sh +login anonymous +force_install_dir /home/container $updatelist +quit
    mkdir -p /home/container/ConanSandbox/Mods
    find /home/container/steamapps/workshop/content/440900 -iname "*.pak" -exec cp {} /home/container/ConanSandbox/Mods \;
    cd /home/container/ConanSandbox/Mods
    rm modlist.txt
    ls |grep "\.pak$" > modlist.txt
    cd /home/container
    printf "\n\nMods updated\n\n"
  fi
fi

# Load Config Files
[ ! -d /home/container/ConanSandbox/Saved/Config/WindowsServer ] && mkdir -p /home/container/ConanSandbox/Saved/Config/WindowsServer/
[ ! -f /home/container/ConanSandbox/Saved/Config/WindowsServer/Engine.ini ] && wget http://raw.githubusercontent.com/lakilla21/testdocker/master/Engine.ini -P /home/container/ConanSandbox/Saved/Config/WindowsServer/
[ ! -f /home/container/ConanSandbox/Saved/Config/WindowsServer/Game.ini ] && wget http://raw.githubusercontent.com/lakilla21/testdocker/master/Game.ini -P /home/container/ConanSandbox/Saved/Config/WindowsServer/
[ ! -f /home/container/ConanSandbox/Saved/Config/WindowsServer/ServerSettings.ini ] && wget http://raw.githubusercontent.com/lakilla21/testdocker/master/ServerSettings.ini -P /home/container/ConanSandbox/Saved/Config/WindowsServer/


# Edit the config with customer settings
sed -i "s/^Port=.*/Port=${SERVER_PORT}/" /home/container/ConanSandbox/Saved/Config/WindowsServer/Engine.ini
sed -i "s/^ServerQueryPort=.*/AdminPassword=${QUERY_PORT}/" /home/container/ConanSandbox/Saved/Config/WindowsServer/Engine.ini
sed -i "s/^ServerName=.*/ServerName=${SERVER_NAME}/" /home/container/ConanSandbox/Saved/Config/WindowsServer/Engine.ini
sed -i "s/^MaxPlayers=.*/MaxPlayers=${PLAYER_SLOTS}/" /home/container/ConanSandbox/Saved/Config/WindowsServer/Game.ini
sed -i "s/^ServerPassword=.*/ServerPassword=${SERVER_PASSWORD}/" /home/container/ConanSandbox/Saved/Config/WindowsServer/ServerSettings.ini
sed -i "s/^AdminPassword=.*/AdminPassword=${ADMIN_PASS}/" /home/container/ConanSandbox/Saved/Config/WindowsServer/ServerSettings.ini

# Replace Startup Variables
MODIFIED_STARTUP=`eval echo $(echo ${STARTUP} | sed -e 's/{{/${/g' -e 's/}}/}/g')`
echo ":/home/container$ ${MODIFIED_STARTUP}"

# Run the Server
${MODIFIED_STARTUP}
