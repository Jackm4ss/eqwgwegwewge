document.addEventListener("DOMContentLoaded", () => {
  initPage();
});

function initPage() {
  pageReveal();
  initInputs();
  initSelects();
  initPasswordToggle();
  initCheckbox();
  initButtons();
  initHoverTilt();
  initRipples();

  initRegisterForm();
  initResendVerification();
  initForgotPasswordForm();
  initLoginForm();
}

/* =========================
   PAGE REVEAL
========================= */
function pageReveal() {
  const card = document.querySelector(".container, .card, .auth-card");
  if (!card) return;

  card.style.opacity = "0";
  card.style.transform = "translateY(24px) scale(.98)";

  requestAnimationFrame(() => {
    card.style.transition = "all .7s cubic-bezier(.2,.8,.2,1)";
    card.style.opacity = "1";
    card.style.transform = "translateY(0) scale(1)";
  });
}

/* =========================
   INPUT INTERACTIONS
========================= */
function initInputs() {
  const fields = document.querySelectorAll("input, textarea");

  fields.forEach((field) => {
    const wrap = field.parentElement;

    field.addEventListener("focus", () => {
      if (wrap) wrap.classList.add("is-focused");
      field.classList.add("glow");
    });

    field.addEventListener("blur", () => {
      if (wrap) wrap.classList.remove("is-focused");
      field.classList.remove("glow");

      if (field.value.trim() !== "") {
        field.classList.add("is-filled");
      } else {
        field.classList.remove("is-filled");
      }
    });

    field.addEventListener("input", () => {
      field.classList.add("typing");
      clearTimeout(field._typingTimer);

      field._typingTimer = setTimeout(() => {
        field.classList.remove("typing");
      }, 180);
    });
  });
}

function initSelects() {
  const selects = document.querySelectorAll("select");

  selects.forEach((select) => {
    select.addEventListener("change", () => {
      if (select.value && select.selectedIndex !== 0) {
        select.classList.add("is-selected");
      } else {
        select.classList.remove("is-selected");
      }
    });
  });
}

/* =========================
   PASSWORD TOGGLE AUTO
========================= */
function initPasswordToggle() {
  const passwordFields = document.querySelectorAll('input[type="password"], .password-field');

  passwordFields.forEach((field) => {
    const wrapper = field.parentElement;
    if (!wrapper || wrapper.querySelector(".toggle-pass")) return;

    wrapper.style.position = "relative";

    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = "toggle-pass";
    btn.setAttribute("aria-label", "Toggle password");
    btn.textContent = "Show";

    Object.assign(btn.style, {
      position: "absolute",
      right: "12px",
      top: "50%",
      transform: "translateY(-50%)",
      border: "none",
      background: "transparent",
      cursor: "pointer",
      fontSize: "12px",
      opacity: ".75",
      padding: "0",
      width: "auto",
      color: "#fff",
      boxShadow: "none"
    });

    btn.addEventListener("click", () => {
      const isHidden = field.type === "password";
      field.type = isHidden ? "text" : "password";
      btn.textContent = isHidden ? "Hide" : "Show";
      btn.style.transform = "translateY(-50%) scale(1.08)";

      setTimeout(() => {
        btn.style.transform = "translateY(-50%) scale(1)";
      }, 120);
    });

    wrapper.appendChild(btn);
  });
}

/* =========================
   CHECKBOX MICRO INTERACTION
========================= */
function initCheckbox() {
  const checks = document.querySelectorAll('input[type="checkbox"]');

  checks.forEach((check) => {
    check.addEventListener("change", () => {
      check.style.transform = "scale(1.18)";
      setTimeout(() => {
        check.style.transform = "scale(1)";
      }, 140);
    });
  });
}

/* =========================
   BUTTON FX
========================= */
function initButtons() {
  const buttons = document.querySelectorAll("button, .btn, input[type='submit']");

  buttons.forEach((btn) => {
    if (btn.classList.contains("toggle-pass")) return;

    btn.addEventListener("mouseenter", () => {
      if (!btn.disabled) btn.style.transform = "translateY(-2px) scale(1.01)";
    });

    btn.addEventListener("mouseleave", () => {
      if (!btn.disabled) btn.style.transform = "translateY(0) scale(1)";
    });

    btn.addEventListener("mousedown", () => {
      if (!btn.disabled) btn.style.transform = "scale(.98)";
    });

    btn.addEventListener("mouseup", () => {
      if (!btn.disabled) btn.style.transform = "translateY(-2px) scale(1.01)";
    });
  });
}

/* =========================
   HOVER TILT CARD
========================= */
function initHoverTilt() {
  const card = document.querySelector(".container, .card, .auth-card");
  if (!card) return;

  card.addEventListener("mousemove", (e) => {
    const rect = card.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;

    const centerX = rect.width / 2;
    const centerY = rect.height / 2;

    const rotateX = ((y - centerY) / centerY) * -3;
    const rotateY = ((x - centerX) / centerX) * 3;

    card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
    card.style.transition = "transform .12s ease";
  });

  card.addEventListener("mouseleave", () => {
    card.style.transform = "perspective(1000px) rotateX(0) rotateY(0)";
    card.style.transition = "transform .35s ease";
  });
}

/* =========================
   RIPPLE EFFECT
========================= */
function initRipples() {
  const rippleTargets = document.querySelectorAll("button, .btn");

  rippleTargets.forEach((btn) => {
    if (btn.dataset.rippleBound === "true") return;
    btn.dataset.rippleBound = "true";

    btn.style.position = "relative";
    btn.style.overflow = "hidden";

    btn.addEventListener("click", function (e) {
      const ripple = document.createElement("span");
      const rect = btn.getBoundingClientRect();
      const size = Math.max(rect.width, rect.height);
      const x = e.clientX - rect.left - size / 2;
      const y = e.clientY - rect.top - size / 2;

      Object.assign(ripple.style, {
        position: "absolute",
        width: `${size}px`,
        height: `${size}px`,
        left: `${x}px`,
        top: `${y}px`,
        borderRadius: "50%",
        background: "rgba(255,255,255,.35)",
        transform: "scale(0)",
        animation: "ripple .6s ease-out",
        pointerEvents: "none"
      });

      ripple.className = "ripple";
      btn.appendChild(ripple);

      setTimeout(() => ripple.remove(), 650);
    });
  });

  injectRippleKeyframes();
}

/* =========================
   REGISTER FORM
========================= */
function initRegisterForm() {
  const form = document.getElementById("registerForm");
  if (!form) return;

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    document.querySelectorAll(".error").forEach((el) => el.remove());

    const submitBtn = form.querySelector("button[type='submit'], input[type='submit']");
    const formMessage = document.getElementById("formMessage");
    const csrfToken = getCsrfToken();

    if (formMessage) formMessage.innerText = "";

    const formData = new FormData(form);
    const payload = Object.fromEntries(formData.entries());
    payload.agree_terms = formData.get("agree_terms") ? "1" : "";

    if (submitBtn) {
      submitBtn.disabled = true;
      rememberButtonLabel(submitBtn);
      setButtonLabel(submitBtn, "Creating...");
      submitBtn.classList.add("loading");
    }

    try {
      const response = await fetch("/api/register", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": csrfToken,
          "Accept": "application/json"
        },
        body: JSON.stringify(payload)
      });

      const data = await response.json();

      if (!response.ok) {
        if (data.errors) {
          Object.entries(data.errors).forEach(([field, messages]) => {
            const input = document.querySelector(`[name="${field}"]`);
            if (input) {
              const err = document.createElement("div");
              err.className = "error";
              err.innerText = messages[0];
              input.parentElement.appendChild(err);
            }
          });
        }

        if (formMessage) {
          formMessage.innerText = data.message || "Registration failed";
        }

        restoreButton(submitBtn);
        return;
      }

      if (submitBtn) {
        setButtonLabel(submitBtn, "Success");
        bounce(submitBtn);
      }

      setTimeout(() => {
        window.location.href = data.redirect;
      }, 700);
    } catch (error) {
      if (formMessage) {
        formMessage.innerText = "Something went wrong. Please try again.";
      }
      restoreButton(submitBtn);
    }
  });
}

/* =========================
   RESEND VERIFICATION
========================= */
function initResendVerification() {
  const resendBtn = document.getElementById("resendBtn");
  const cooldown = document.getElementById("cooldown");
  const msg = document.getElementById("msg");

  if (!resendBtn || !cooldown) return;

  const csrfToken = getCsrfToken();
  const email = resendBtn.dataset.email;
  let seconds = 60;
  let timer = null;

  const startCooldown = () => {
    resendBtn.disabled = true;
    cooldown.innerText = `You can request a new verification link in ${seconds} seconds.`;

    timer = setInterval(() => {
      seconds--;
      cooldown.innerText = `You can request a new verification link in ${seconds} seconds.`;

      if (seconds <= 0) {
        clearInterval(timer);
        timer = null;
        resendBtn.disabled = false;
        cooldown.innerText = "You can request a new verification email now.";
      }
    }, 1000);
  };

  startCooldown();

  resendBtn.addEventListener("click", async () => {
    if (!email) {
      if (msg) {
        msg.innerText = "Verification email is missing. Please return to the registration page.";
      }
      return;
    }

    resendBtn.disabled = true;
    setButtonLabel(resendBtn, "Sending...");
    if (msg) msg.innerText = "";

    try {
      const res = await fetch("/api/email/resend-verification", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": csrfToken,
          "Accept": "application/json"
        },
        body: JSON.stringify({ email })
      });

      const data = await res.json();

      if (msg) {
        msg.innerText = data.message || "Request sent";
      }

      setButtonLabel(resendBtn, "Resend Verification Email");
      seconds = 60;

      if (timer) clearInterval(timer);
      startCooldown();
    } catch (error) {
      if (msg) {
        msg.innerText = "Failed to resend verification email.";
      }
      setButtonLabel(resendBtn, "Resend Verification Email");
      resendBtn.disabled = false;
    }
  });
}

/* =========================
   FORGOT PASSWORD
========================= */
function initForgotPasswordForm() {
  const form = document.getElementById("forgotPasswordForm");
  if (!form) return;

  form.addEventListener("submit", () => {
    const submitBtn = form.querySelector("button[type='submit'], input[type='submit']");
    const message = document.getElementById("forgotPasswordMessage");

    if (message) message.innerText = "";

    if (submitBtn) {
      submitBtn.disabled = true;
      rememberButtonLabel(submitBtn);
      setButtonLabel(submitBtn, "Sending...");
      submitBtn.classList.add("loading");
    }
  });
}

/* =========================
   LOGIN
========================= */
function initLoginForm() {
  const form = document.getElementById("loginForm") || document.getElementById("formAuthentication");
  if (!form) return;

  form.addEventListener("submit", () => {
    const submitBtn = form.querySelector("button[type='submit'], input[type='submit']");
    const message = document.getElementById("loginMessage");

    if (message) message.innerText = "";

    if (submitBtn) {
      submitBtn.disabled = true;
      rememberButtonLabel(submitBtn);
      setButtonLabel(submitBtn, "Submitting...");
      submitBtn.classList.add("loading");
    }
  });
}

/* =========================
   HELPERS
========================= */
function getCsrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";
}

function restoreButton(btn) {
  if (!btn) return;
  btn.disabled = false;
  btn.classList.remove("loading");
  if (btn.dataset.originalText) {
    btn.textContent = btn.dataset.originalText;
  }
}

function rememberButtonLabel(btn) {
  if (!btn || btn.dataset.originalText) return;
  btn.dataset.originalText = btn.textContent.trim();
}

function setButtonLabel(btn, label) {
  if (!btn) return;
  btn.textContent = label;
}

function bounce(el) {
  if (!el || typeof el.animate !== "function") return;

  el.animate(
    [
      { transform: "scale(1)" },
      { transform: "scale(1.08)" },
      { transform: "scale(.98)" },
      { transform: "scale(1)" }
    ],
    {
      duration: 420,
      easing: "cubic-bezier(.2,.8,.2,1)"
    }
  );
}

function injectRippleKeyframes() {
  if (document.getElementById("ripple-style")) return;

  const style = document.createElement("style");
  style.id = "ripple-style";
  style.innerHTML = `
    @keyframes ripple {
      to {
        transform: scale(4);
        opacity: 0;
      }
    }

    .glow {
      box-shadow: 0 0 0 4px rgba(45, 92, 255, 0.10);
      transition: box-shadow .2s ease;
    }

    .typing {
      transform: scale(1.01);
      transition: transform .15s ease;
    }

    .loading {
      filter: saturate(1.1);
      letter-spacing: .2px;
    }
  `;
  document.head.appendChild(style);
}
