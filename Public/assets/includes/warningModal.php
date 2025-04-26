<!-- warningModal.php -->
<div id="warningModal" class="warningModal">
  <div class="warningModal-content">
    <h2>Warnung: Sensible Inhalte</h2>
    <p>Diese Seite enthält realistische Darstellungen von simulierten Notfall- und Unfallszenarien. 
      Zu Übungszwecken werden Verletzungsmuster und medizinische Notfälle nachgestellt (z.B. mit Kunstblut).
      Diese Inhalte sind nicht für Kinder oder sensible Personen geeignet und werden auf eigene Verantwortung betrachtet.</p>
    <div class="warningModal-actions">
      <button onclick="continueToPage()">Ich habe verstanden und möchte fortfahren</button>
      <button onclick="goToHome()">Zurück zur Startseite</button>
    </div>
  </div>
</div>

<style>
  body.modal-active {
    overflow: hidden;
  }

  body.modal-active .page-content {
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
    background-color: rgba(0, 0, 0, 0.7);
    align-items: center;
    justify-content: center;
  }

  .warningModal-content {
    background-color: #fff;
    color: #333;
    padding: 2rem;
    border-radius: 12px;
    width: 90%;
    max-width: 550px;
    text-align: center;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
  }

  .warningModal h2 {
    color: #A72920;
    margin-bottom: 1.5rem;
    font-size: 1.5rem;
    position: relative;
    padding-bottom: 0.5rem;
  }

  .warningModal h2:after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 60px;
    height: 3px;
    background-color: #A72920;
  }

  .warningModal p {
    font-size: 1rem;
    line-height: 1.6;
    margin-bottom: 1.8rem;
    color: #555;
  }

  .warningModal-actions {
    display: flex;
    flex-direction: column;
    gap: 12px;
  }

  .warningModal-actions button {
    width: 100%;
    margin: 0;
    padding: 14px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s ease;
  }

  .warningModal-actions button:first-child {
    background-color: #A72920;
    color: white;
  }

  .warningModal-actions button:last-child {
    background-color: #f0f0f0;
    color: #555;
    border: 1px solid #ddd;
  }

  .warningModal-actions button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  }

  @media (min-width: 768px) {
    .warningModal-actions {
      flex-direction: row;
      justify-content: center;
      gap: 15px;
    }

    .warningModal-actions button {
      width: auto;
      padding: 14px 25px;
    }
  }
</style>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const warningModal = document.getElementById("warningModal");

    if (warningModal) {
      warningModal.style.display = "flex";
      document.body.classList.add('modal-active');
    }
  });

  function continueToPage() {
    const warningModal = document.getElementById("warningModal");
    warningModal.style.display = "none";
    document.body.classList.remove('modal-active');
  }

  function goToHome() {
    window.location.href = "/";
  }
</script>
