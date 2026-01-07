document.addEventListener("DOMContentLoaded", () =>{
    drawEmptyBoard('#board');
});

function drawEmptyBoard(selector){
    var container = document.querySelector(selector);
    var pawn_color = 'W'
    if(!container) return;

    var t = '<table id="Backgammon_table">';

    function makeCell(id,type){
        var color = (id%2==0) ? 'even':'odd';
        return '<td class="board_section" id="section_'+id+'">'+
        '<div class="triangle '+type+' '+ color+'">'+
        '<span class="debug_number">'+id+'</span>'+
        '</div></td>';

    }
    
    if(pawn_color=='W'){
        t+='<tr>';
        for(var i = 1;i<=6;i++){
            t+= makeCell(i,'down');
        }
        t+= '<td class="bar_center"><div class="dice" id="dice_1">1</div></td>';
        for(var i = 7;i<=12;i++){
            t+= makeCell(i,'down');
        }
        t+= '</tr>';
        t+= '<tr>';
        for(var j = 24;j>=19;j--){
            t+= makeCell(j,'up');
        }
        t+='<td class="bar_center"><div class="dice" id="dice_2">2</div></td>';
        for(var j = 18;j>=13;j--){
            t+= makeCell(j,'up');
        }
        t+='</tr>'
    }
    else{
         t+='<tr>';
        for(var i = 13;i<=18;i++){
            t+= makeCell(i,'down');
        }
        t+= '<td class="bar_center"><div class="dice" id="dice_1">1</div></td>';
        for(var i = 19;i<=24;i++){
            t+= makeCell(i,'down');
        }
        t+= '</tr>';
        t+= '<tr>';
        for(var j = 12;j>=7;j--){
            t+= makeCell(j,'up');
        }
        t+='<td class="bar_center"><div class="dice" id="dice_2">2</div></td>';
        for(var j = 6;j>=1;j--){
            t+= makeCell(j,'up');
        }
        t+='</tr>'
    }
    t+= '</table>';
    container.innerHTML = t;
}

