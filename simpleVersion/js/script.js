const cards = document.querySelectorAll('.game-card');
let clickedCard = null;
let preventClick = false;

(function shuffleCards() {
  cards.forEach(card => {
    const shuffeledPosition = Math.floor(Math.random() * cards.length); 
    card.style.order = shuffeledPosition;
  });
})();

cards.forEach(card => {
  card.addEventListener('click', () =>{

    if (preventClick || card === clickedCard || card.classList.contains('done')){
      return;
    }

    card.classList.remove('unflipped');
    card.classList.add('done');

    if(!clickedCard) {
      clickedCard = card;
    } else if (clickedCard) {

      if (clickedCard.getAttribute('data-img') !== card.getAttribute('data-img')){
        preventClick = true;
        setTimeout(() => {

          clickedCard.className = clickedCard.className.replace('done', '') + 'unflipped';
          card.className = card.className.replace('done', '') + 'unflipped';

          clickedCard = null;
          preventClick = false;
        }, 500);
      } else {
        
        clickedCard = null;
      }
    } 
  });
});

