let lastRender = 0;

const infoTextElement  = document.getElementById("info");
const wholeWeekElement = document.getElementById("wholeWeek");
const weekModeElement  = document.getElementById("weekMode");
const svgElement       = document.getElementById("svg");

let wholeWeek = false;

wholeWeekElement.addEventListener("click", () => renderSchedule());
svgElement.setAttribute("viewBox", "0 0 " + screen.width + " " + screen.height);

const dataAreaElement = document.getElementById("data-input");

renderSchedule();

document.addEventListener("visibilitychange", () => {
    if(!document.hidden){
        renderSchedule();
    }
});

async function renderSchedule(){
    wholeWeek = wholeWeekElement.checked;
    const currentTime = Date.now();

    // Användaren försöker rendera för fort
    if(currentTime - lastRender < 1000)
        return;

    lastRender = currentTime;
    svgElement.innerHTML = ""; // Rensa tidigare rendering

    let url = "schedule" + 
        "?width=" + screen.width +
        "&height=" + screen.height +
        "&wholeWeek=" + wholeWeek;

    let res = await fetch(url);
    if(!res.ok)
        return;

    dataAreaElement.style.display = "none";
    weekModeElement.style.display = "block";

    let data = await res.json();
    showTimeInfo(data);
    renderSVG(data);
}

function showTimeInfo(data){
    if(wholeWeek){
        showInfoText("");
        return;
    }

    // Sorterar datan efter lektionens sluttid 
    data.parsed.sort((a, b) => a.timeEnd - b.timeEnd);
        
    let t = getTimeSinceMidnight() / 1000;
    let lastLessonEnd = 0;

    for (let i in data.parsed) {
        let info = data.parsed[i];
        let timeEnd   = parseInt(info.timeEnd);
        let timeStart = parseInt(info.timeStart);
    
        if(timeEnd > lastLessonEnd)
            lastLessonEnd = timeEnd;

        // Om tiden är innan sluttiden men efter starttiden - användaren har lektion just nu
        if (t < timeEnd && t > timeStart) {
            let timeDiff = Math.round((timeEnd - t) / 60);
            let unit = "minut" + (timeDiff < 0 || timeDiff > 1 ? "er" : "");

            showInfoText("Du slutar " + convertTime(timeEnd * 1000) + " (om " + timeDiff + " " + unit + ")");

        // Om tiden är innan starttiden men efter den tidigare lektionens sluttid - användaren är mellan lektioner
        } else if (i == 0 || t < timeStart && t > data.parsed[i - 1].timeEnd) {
            let timeDiff = Math.round(parseInt(info.timeStart - t) / 60);
            let unit = "minut" + (timeDiff < 0 || timeDiff > 1 ? "er" : "");

            showInfoText("Du börjar " + info.name + " " + convertTime(timeStart * 1000) + " (om " + timeDiff + " " + (unit) + ") i " + info.room);
        }
    }
    
    // Om tiden är efter den sista lektionens slut
    if (t > lastLessonEnd)
        showInfoText("Du har slutat :)");
}

function convertTime(ms) {
    let seconds = ms / 1000;
    let hours = parseInt(seconds / 3600);
    seconds = seconds % 3600;
    let minutes = parseInt(seconds / 60);
    seconds = seconds % 60;

    return (hours > 9 ? hours : "0" + hours) + ":" + (minutes > 9 ? minutes : "0" + minutes);
}

function renderSVG(data){
    // Nedanstående metoder ritar schemat enligt Skola24s format
    let lowest = 0;
    for (let i in data.boxList) {
        let box = data.boxList[i];
        let rect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        
        rect.setAttribute('x', (box.x + 1).toString());
        rect.setAttribute('y', (box.y + 1).toString());
        rect.setAttribute('width', box.width.toString());
        rect.setAttribute('height', box.height.toString());
        rect.setAttribute('shape-rendering', 'crispEdges');
        rect.style.fill = box.bColor;
        rect.style.stroke = box.fColor;
        rect.style.strokeWidth = '1';
        
        if (box.fColor === box.bColor)
            rect.style.strokeWidth = '0';

        if (box.y + box.height > lowest)
            lowest = box.y + box.height;

        svg.appendChild(rect);
    }

    for (let i in data.textList) {
        let text = data.textList[i];
        let label = document.createElementNS('http://www.w3.org/2000/svg', 'text');

        label.textContent = text.text;
        label.style.fontSize = text.fontsize + "px";
        label.style.fontFamily = 'Open Sans';
        label.style.fill = text.fColor;
        label.setAttribute('x', (text.x + 1).toString());
        label.setAttribute('y', (text.y + 1 + text.fontsize).toString());
        
        if (text.bold)
            label.style.fontWeight = 'bold';
    
        if (text.italic)
            label.style.fontStyle = 'italic';
        
        label.style.pointerEvents = 'none';
        svg.appendChild(label);
    }

    for (let i in data.lineList) {
        let line = data.lineList[i];
        let element = document.createElementNS('http://www.w3.org/2000/svg', 'line');

        element.setAttribute('x1', (line.p1x + 1).toString());
        element.setAttribute('y1', (line.p1y + 1).toString());
        element.setAttribute('x2', (line.p2x + 1).toString());
        element.setAttribute('y2', (line.p2y + 1).toString());
        element.setAttribute('stroke', line.color);
        svg.appendChild(element);
    }
}

function showInfoText(text){
    infoTextElement.innerText = text;
}

function getTimeSinceMidnight() {
    const date = new Date();
    date.setHours(0, 0, 0, 0);

    return new Date().getTime() - date;
}