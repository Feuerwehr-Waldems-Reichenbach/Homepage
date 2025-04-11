<!-- warning-modal.php -->

<div id="pageContent" class="blur-wrapper">
</div>

<div id="warningModal" class="warningModal">
  <div class="warningModal-content">
    <h2>⚠️ Warnung: Sensible Inhalte</h2>
    <p>Diese Seite enthält realistische Darstellungen von simulierten Verletzungen und Unfallszenarien (Kunstblut, etc.). Diese Inhalte könnten für einige Besucher, insbesondere Kinder, verstörend sein.</p>
    <div class="warningModal-actions">
      <button onclick="continueToPage()">Ich bin mir bewusst - Weiter zur Seite</button>
      <button onclick="goToHome()">Zurück zur Startseite</button>
    </div>
  </div>
</div>

<style>
  body.modal-active {
    overflow: hidden;
  }
  
  .blur-wrapper {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: -1;
  }

  body.modal-active > *:not(#warningModal) {
    filter: blur(8px);
    transition: filter 0.5s ease;
  }

  .warningModal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100vw;
    height: 100vh;
    background-color: rgba(0,0,0,0.7);
    align-items: center;
    justify-content: center;
    filter: none !important;
  }

  .warningModal-content {
    background-color: #fff;
    color: #111;
    padding: 30px;
    border-radius: 10px;
    width: 90%;
    max-width: 500px;
    text-align: center;
    box-shadow: 0 0 20px rgba(0,0,0,0.3);
    filter: none !important;
  }

  .warningModal h2 {
    color: #A72920;
    margin-bottom: 20px;
  }

  .warningModal p {
    font-size: 1.1em;
    line-height: 1.5;
    margin-bottom: 25px;
  }

  .warningModal-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
  }

  .warningModal-actions button {
    width: 100%;
    margin: 0;
    padding: 12px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
    transition: background-color 0.3s ease;
  }

  .warningModal-actions button:first-child {
    background-color: #A72920;
    color: white;
  }

  .warningModal-actions button:last-child {
    background-color: #6c757d;
    color: white;
  }

  .warningModal-actions button:hover {
    opacity: 0.9;
  }

  @media (min-width: 768px) {
    .warningModal-actions {
      flex-direction: row;
      justify-content: center;
    }

    .warningModal-actions button {
      width: auto;
    }
  }
</style>

<script>
  window.onload = function() {
    document.getElementById("warningModal").style.display = "flex";
    document.body.classList.add('modal-active');
  }

  function continueToPage() {
    document.getElementById("warningModal").style.display = "none";
    document.body.classList.remove('modal-active');
  }

  function goToHome() {
    window.location.href = "/";
  }
</script>
