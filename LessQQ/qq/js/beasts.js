window.addEventListener('DOMContentLoaded', () => {

    const getSort = ({ target }) => {
        const order = (target.dataset.order = -(target.dataset.order || -1));
        const index = [...target.parentNode.cells].indexOf(target);
        const collator = new Intl.Collator(['en', 'ru'], { numeric: true });
        const comparator = (index, order) => (a, b) => order * collator.compare(
            a.children[index].innerHTML,
            b.children[index].innerHTML
        );

        for(const tBody of target.closest('table').tBodies)
            tBody.append(...[...tBody.rows].sort(comparator(index, order)));

        for(const cell of target.parentNode.cells)
            cell.classList.toggle('sorted', cell === target);
    };

    document.querySelectorAll('.table_sort thead').forEach(tableTH => tableTH.addEventListener('click', () => getSort(event)));


class Player {
    constructor(name, class_name, dps, hps, parentSelector) {
       
        this.name = name;
        this.class_name = class_name;
        this.dps = dps;
        this.hps = hps;
        this.parent = document.querySelector(parentSelector);
    }

    render() {
        const element = document.createElement('tr');
        element.innerHTML = `
        <tr>
            <td>${this.name}</td>
            <td>${this.class_name}</td>
            <td>${this.dps}</td>
            <td>${this.hps}</td>
        </tr>
        `;
        this.parent.append(element);
    }
}

const getResource = async (url) => {
    const res = await fetch(url);

    if(!res.ok) {
       throw new Error(`Could not fetch ${url}, status: ${res.status}`);
    }
    return await res.json();
};

getResource('https://sirus.su/api/base/57/details/bossfight/2586773?lang=ru')
    .then(data => {
        data.data.players.forEach(({ character : {name, class_name}, dps, hps }) => {
            new Player( name, class_name, dps, hps,  ".players .container" ).render();
        }) ;
    })
    

    // fetch('https://sirus.su/api/base/57/leader-board/bossfights/boss-kill/690272')
    // .then(response => response.json())
    // .then(json => console.log(json.data.players))



    
});
