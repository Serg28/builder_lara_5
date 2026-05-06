

function slug_gen(string)
{
    string = string.toLowerCase();

    string = string.replace(/ж/g,"zh");
    string = string.replace(/ё/g,"yo");
    string = string.replace(/й/g,"i");
    string = string.replace(/ю/g,"yu");
    string = string.replace(/ь/g,"");
    string = string.replace(/ч/g,"ch");
    string = string.replace(/щ/g,"sh");
    string = string.replace(/ц/g,"c");
    string = string.replace(/у/g,"u");
    string = string.replace(/к/g,"k");
    string = string.replace(/е/g,"e");
    string = string.replace(/н/g,"n");
    string = string.replace(/г/g,"g");
    string = string.replace(/ш/g,"sh");
    string = string.replace(/з/g,"z");
    string = string.replace(/х/g,"h");
    string = string.replace(/ъ/g,"");
    string = string.replace(/ф/g,"f");
    string = string.replace(/ы/g,"y");
    string = string.replace(/в/g,"v");
    string = string.replace(/а/g,"a");
    string = string.replace(/п/g,"p");
    string = string.replace(/р/g,"r");
    string = string.replace(/о/g,"o");
    string = string.replace(/л/g,"l");
    string = string.replace(/д/g,"d");
    string = string.replace(/э/g,"e");
    string = string.replace(/я/g,"ja");
    string = string.replace(/с/g,"s");
    string = string.replace(/м/g,"m");
    string = string.replace(/и/g,"i");
    string = string.replace(/т/g,"t");
    string = string.replace(/б/g,"b");
    string = string.replace(/Ё/g,"yo");
    string = string.replace(/Й/g,"i");
    string = string.replace(/Ю/g,"yu");
    string = string.replace(/Ч/g,"ch");
    string = string.replace(/і/g,"i");
    string = string.replace(/є/g,"e");
    string = string.replace(/ї/g,"i");
    string = string.replace(/ґ/g,"g");
    string = string.replace(/:/g,"");
    string = string.replace(/;/g,"");
    string = string.replace(/@/g,"");
    string = string.replace(/#/g,"");
    string = string.replace(/\$/g,"");
    string = string.replace(/\^/g,"");
    string = string.replace(/\*/g,"");
    string = string.replace(/\%/g,"");
    string = string.replace(/\+/g,"");
    string = string.replace(/=/g,"");
    string = string.replace(/®/g,"");
    string = string.replace(/\(/g,"");
    string = string.replace(/\)/g,"");
    string = string.replace(/ /g,"-");
    string = string.replace(/\./g,"_");
    string = string.replace(/,/g,"_");
    string = string.replace(/«/g,"");
    string = string.replace(/!/g,"");
    string = string.replace(/&/g,"");
    string = string.replace(/\|/g,"");
    string = string.replace(/\?/g,"_");
    string = string.replace(/»/g,"");
    string = string.replace(/'/g,"");
    string = string.replace(/"/g,"");
    string = string.replace(/\[/g,"");
    string = string.replace(/\]/g,"");
    string = string.replace(/’/g,"");
    string = string.replace(/</g,"");
    string = string.replace(/—/g,"");
    string = string.replace(/–/g,"-");
    string = string.replace(/€/g,"");

    string = string.replace(/>/g,"");
    string = string.replace(/`/g,"");
    string = string.replace(/~/g,"");
    string = string.replace(/}/g,"");
    string = string.replace(/{/g,"");
    string = string.replace(/№/g,"");
    string = string.replace(/\//g,"");
    string = string.replace(/\\/g,"");
    return string;
}


