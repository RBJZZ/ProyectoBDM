document.addEventListener("DOMContentLoaded",function(){
    const btn = document.getElementById("loginbtn");

    btn.addEventListener("click", function(event){
        event.preventDefault();
        window.location.href=`feed.html`;
    });
});

