const m=(i,t="IDR")=>new Intl.NumberFormat(t==="IDR"?"id-ID":"en-US",{style:"currency",currency:t,minimumFractionDigits:t==="IDR"?0:2,maximumFractionDigits:t==="IDR"?0:2}).format(i);export{m as f};
