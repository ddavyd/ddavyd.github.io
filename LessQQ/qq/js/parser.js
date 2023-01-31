window.addEventListener('DOMContentLoaded', () => {

    const intervalForUpdate = 30000;

    let timerId = null;
    let records = [];
    
    
    let getExtraInfo = async function(recordId) {
        let responceBossKillInfo = await fetch('https://sirus.su/api/base/57/leader-board/bossfights/boss-kill/' + recordId);
        let dataBossKillInfo = await responceBossKillInfo.json();
    
        if (dataBossKillInfo.players) {
    
            let table = dataBossKillInfo.players
            for (let i = 1;i<table.length;i++)
            {
                let tableForAdd = [
                    "name" = table[i].character.name,
                    "cname" = table[i].character.class_name,
                    "dps" = table[i].character.dps,
                    "hps" = table[i].character.hps
                ]
                let openRequest = indexedDB.open(guild, 1);
            };
    
        }
    };
    
    
    
    let getExtraInfoWrapper = async (record) => {
    
        if (records.indexOf(record.id) < 0 && record.boss_name) {
            await getExtraInfo(record.id);
            records.push(record.id);
        }
    }
    
    let getLatestKills = async function() {
        let responce = await fetch('https://api.sirus.su/api/base/57/leader-board/bossfights/latest?realm=57')
        .catch((error) => {
            console.error('Error:', error);
        });
        let listOfKills = await responce.json();
        let date_ob = new Date();
        await Promise.all(listOfKills['data'].map(record => getExtraInfoWrapper(record)))
        console.log('updated ' + date_ob.getHours() + ":" + date_ob.getMinutes() + ":" + date_ob.getSeconds());
        timerId = setTimeout(() => {
            getLatestKills();
        }, intervalForUpdate)
    }

    
});