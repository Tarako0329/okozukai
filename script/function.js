//js 共通関数格納予定
const console_log=(log,lv)=>{
  //lv:all=全環境 undefined=本番以外
  //console.log(lv)
  if(lv==="all"){
    console.log(log)
  }/*else if(lv==="lv2" && KANKYO!=="Product"){
    console.log(log)
  }*/else if((lv==="lv3" || lv===undefined) && (KANKYO!=="Product")){
    //console.log(KANKYO)
    //console.log(lv)
    console.log(log)
  }else{
    return 0;
  }
}

