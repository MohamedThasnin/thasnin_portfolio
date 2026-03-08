// Mobile menu
const menuBtn = document.getElementById("menuBtn");
const mobileMenu = document.getElementById("mobileMenu");
if (menuBtn && mobileMenu) {
  menuBtn.addEventListener("click", () => mobileMenu.classList.toggle("show"));
  mobileMenu.querySelectorAll("a").forEach(a =>
    a.addEventListener("click", () => mobileMenu.classList.remove("show"))
  );
}

// Typing roles
const roles = window.__ROLES__ || ["Web Developer","Software Developer","Problem Solver"];
const typingEl = document.getElementById("typing");

let i = 0, j = 0;
let deleting = false;

function tick(){
  if(!typingEl) return;
  const word = roles[i % roles.length];
  const shown = word.slice(0, j);

  typingEl.textContent = shown + (Math.floor(Date.now()/500)%2 ? " |" : "");

  if(!deleting){
    j++;
    if(j > word.length){
      deleting = true;
      setTimeout(tick, 900);
      return;
    }
  }else{
    j--;
    if(j < 0){
      deleting = false;
      i++;
      j = 0;
    }
  }
  setTimeout(tick, deleting ? 45 : 70);
}
tick();
