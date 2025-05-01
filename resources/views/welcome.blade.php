<div class="logo">
      <svg width="800px" height="" viewBox="0 0 800 160">  
       <text 
          fill="none" 
          stroke="#fff" 
          x="0"
          y="120"
          stroke-width="5" 
          font-size="120" 
          font-family="'Raleway', sans-serif" 
          font-weight="800" 
          class="is-active">
          Coming Soon
        </text>
        <path class="underline" data-name="Path 1" d="M107,318.311s18.935-20,32.92,0,31.85,0,31.85,0,18.736-19.584,33.161,0,29.294,0,29.294,0,23.385-16.934,35.261,0,34.535,10.772,35.875,0,32.49-14.143,33.135,0,41.233,11.789,42.7,0,33.415-7.747,32.62,0,25.6,19.335,34.531,0" transform="translate(60 -188.421)" fill="none" stroke="#00BFFF" stroke-width="3"/>
     </svg>
  </div>
</div>

<style>
/* $size: 250px; */

@import url('https://fonts.googleapis.com/css2?family=Raleway:wght@600&display=swap');

* {
 margin: 0;
 padding: 0;
 box-sizing: border-box;
  
body {
 background: black url("https://source.unsplash.com/random/1600x900?flower") no-repeat fixed center;
 width: 100vw;
 height: 100vh;
 display: flex;
 align-items: center;
}
}

.logo {
 width: 100%;
 display: flex;
 font-size: 120px;
 color: white;
 justify-content: center;
 align-items: center;
 padding: 25px;
 background: rgba(0,0,0,0.5);
  position: relative;
 
  text {
   stroke-width:5px ;
   stroke-dasharray: 900;
   stroke-dashoffset: -900;
   animation: text 4s forwards 1;
   animation-delay: 1s;
  }
  
  .underline{
    stroke-dasharray: 900;
    stroke-dashoffset: 900;
    animation: underline 4s forwards 1;
    animation-delay: 5s;
  }

}

@keyframes text {
 75% {
  fill: transparent;
  stroke-dashoffset: 0;
  stroke-width:5px ;
 }
 100% {
  fill: #F3F3F3;
  stroke-dashoffset: 0;
  stroke-width: 0;
 }
}

@keyframes underline {
 75% {
  stroke-dashoffset: 0;
 }
 100% {
  stroke-dashoffset: 0;
 }
}
</style>