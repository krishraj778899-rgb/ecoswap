const API_BASE = "/backend/api/";
let activeConversationId = Number(new URLSearchParams(location.search).get("conversation_id")) || null;
let currentUserId = null;
let chatTimer = null;

document.addEventListener("DOMContentLoaded", () => {
  document.getElementById("messageForm").addEventListener("submit", sendMessage);
  document.getElementById("refreshChats").addEventListener("click", loadConversations);
  loadConversations();
});

async function api(url, options={}) {
  const r = await fetch(API_BASE + url, {credentials:"include", ...options});
  const d = await r.json().catch(()=>({success:false,message:"Invalid server response."}));
  if (!r.ok || !d.success) throw new Error(d.message || "Request failed.");
  return d;
}

async function loadConversations(){
  try{
    const d=await api("chat-list.php");
    renderConversations(d.conversations||[]);
    if(activeConversationId){ await loadConversation(activeConversationId); }
    else if((d.conversations||[]).length){ await loadConversation(Number(d.conversations[0].id)); }
  }catch(e){
    if(e.message.toLowerCase().includes("login")){ location.href="index.html#browse"; return; }
    document.getElementById("conversationList").innerHTML=`<div class="chat-empty">${escapeHtml(e.message)}</div>`;
  }
}

function renderConversations(list){
  const el=document.getElementById("conversationList");
  if(!list.length){el.innerHTML='<div class="chat-empty">No swap chats yet.<br>Open an item and tap Request Swap & Chat.</div>';return;}
  el.innerHTML=list.map(c=>`<button class="conversation-card ${Number(c.id)===Number(activeConversationId)?'active':''}" onclick="openConversation(${Number(c.id)})">
    ${c.image?`<img src="${c.image}" alt="">`:'<div class="conversation-avatar"><i class="fa-solid fa-box"></i></div>'}
    <div class="conversation-text"><strong>${escapeHtml(c.item_name)}</strong><small>${escapeHtml(c.last_message||'Start the conversation')}</small></div>
  </button>`).join("");
}

async function openConversation(id){ activeConversationId=Number(id); history.replaceState({},"",`chat.html?conversation_id=${encodeURIComponent(id)}`); await loadConversation(id); loadConversations(); }
window.openConversation=openConversation;

async function loadConversation(id){
  try{
    const d=await api(`chat-messages.php?conversation_id=${encodeURIComponent(id)}`);
    currentUserId=Number(d.current_user_id); renderHeader(d.conversation); renderMessages(d.messages||[]); enableComposer(true);
    if(chatTimer) clearInterval(chatTimer);
    chatTimer=setInterval(async()=>{try{const x=await api(`chat-messages.php?conversation_id=${encodeURIComponent(id)}`); renderMessages(x.messages||[]);}catch(_){}},7000);
  }catch(e){ showToast(e.message,"error"); }
}

function renderHeader(c){
  const h=document.getElementById("chatHeader");
  h.innerHTML=`<div class="chat-title"><div class="conversation-avatar"><i class="fa-solid fa-comments"></i></div><div><h2>${escapeHtml(c.item_name)}</h2><div class="chat-meta">Owner: ${escapeHtml(c.owner_name)} · Location: ${escapeHtml(c.location||'Not provided')}</div><div class="item-chat-contact">Phone: <a href="tel:${String(c.phone||'').replace(/[^0-9+]/g,'')}">${escapeHtml(c.phone||'Not provided')}</a></div></div></div>`;
}

function renderMessages(messages){
  const el=document.getElementById("messageList");
  if(!messages.length){el.innerHTML='<div class="chat-empty">No messages yet. Say hello and discuss your swap or price.</div>';return;}
  const wasNearBottom=el.scrollHeight-el.scrollTop-el.clientHeight<100;
  el.innerHTML=messages.map(m=>`<div class="message ${Number(m.sender_id)===Number(currentUserId)?'mine':''}"><div class="message-author">${escapeHtml(m.sender_name)}</div><div class="message-body">${escapeHtml(m.body||'')}</div>${m.offer_price!==null&&m.offer_price!==''?`<div class="offer">Offer: ₹${escapeHtml(m.offer_price)}</div>`:''}<div class="message-time">${formatDate(m.created_at)}</div></div>`).join("");
  if(wasNearBottom) el.scrollTop=el.scrollHeight;
}

function enableComposer(enabled){ document.getElementById("messageInput").disabled=!enabled; document.getElementById("offerPrice").disabled=!enabled; document.getElementById("sendButton").disabled=!enabled; }

async function sendMessage(e){
  e.preventDefault(); if(!activeConversationId)return;
  const body=document.getElementById("messageInput").value.trim(); const offer=document.getElementById("offerPrice").value;
  if(!body && !offer){showToast("Write a message or enter an offer.","error");return;}
  try{
    await api(`chat-messages.php?conversation_id=${encodeURIComponent(activeConversationId)}`,{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({body,offer_price:offer||null})});
    document.getElementById("messageInput").value="";document.getElementById("offerPrice").value="";await loadConversation(activeConversationId);loadConversations();
  }catch(e){showToast(e.message,"error");}
}

function formatDate(v){try{return new Date(v).toLocaleString([], {month:"short",day:"numeric",hour:"2-digit",minute:"2-digit"});}catch{return "";}}
function escapeHtml(v){const d=document.createElement("div");d.textContent=v??"";return d.innerHTML;}
function showToast(msg,type="success"){const t=document.getElementById("toast"),m=document.getElementById("toastMessage");m.textContent=msg;t.classList.add("show");t.dataset.type=type;setTimeout(()=>t.classList.remove("show"),2800);}
