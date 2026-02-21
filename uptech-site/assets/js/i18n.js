/* assets/js/i18n.js */
(() => {
  const I18N = {
    fr: {
      // NAV
      "nav.home": "Accueil",
      "nav.expertises": "Expertises",
      "nav.sectors": "Secteurs",
      "nav.about": "À propos",
      "nav.contact": "Contact",

      // FOOTER
      "footer.tagline": "Parce que le numérique n’est pas un luxe, mais une nécessité.",
      "footer.location": "Lomé, Togo",
      "footer.contact": "Contact",

      // HOME
      "home.kicker": "Transformation digitale • Technologie • Exécution",
      "home.title": "Nous accompagnons les organisations dans leur transformation digitale.",
      "home.subtitle": "De la conception web à l’acquisition, en passant par l’automatisation et le conseil, nous construisons des solutions numériques structurées, scalables et orientées performance.",
      "home.ctaPrimary": "Demander un devis",
      "home.ctaSecondary": "Explorer nos expertises",

      "home.metric1.label": "Approche",
      "home.metric1.value": "Tech & stratégique",
      "home.metric1.note": "Architecture, systèmes, gouvernance.",
      "home.metric2.label": "Orientation",
      "home.metric2.value": "Performance",
      "home.metric2.note": "Conversion, tracking, optimisation.",
      "home.metric3.label": "Résultat",
      "home.metric3.value": "Exécution claire",
      "home.metric3.note": "Roadmap, delivery, itération.",

      "benefits.title": "Ce que tu gagnes",
      "benefits.subtitle": "Une exécution structurée, des décisions basées sur les données, et une base technique qui tient dans le temps.",
      "benefits.b1.title": "Clarté",
      "benefits.b1.desc": "Périmètre, livrables, priorités et timeline définis.",
      "benefits.b2.title": "Mesure",
      "benefits.b2.desc": "Tracking, KPI, et optimisation en continu.",
      "benefits.b3.title": "Fiabilité",
      "benefits.b3.desc": "Systèmes maintenables, documentés, scalables.",

      "home.expertises.title": "Expertises",
      "home.expertises.subtitle": "4 piliers structurants pour construire, déployer et optimiser ton système digital.",
      "home.expertises.learn": "En savoir plus →",
      "home.expertises.web.title": "Architecture & Développement Web",
      "home.expertises.web.desc": "Sites et plateformes rapides, scalables, optimisés conversion et maintenables.",
      "home.expertises.acq.title": "Publicité & Acquisition Digitale",
      "home.expertises.acq.desc": "Stratégie, tracking, campagnes et optimisation continue orientée ROI.",
      "home.expertises.auto.title": "Automatisation & Digitalisation",
      "home.expertises.auto.desc": "Process, outils, automatisations simples pour gagner du temps et de la fiabilité.",
      "home.expertises.transfo.title": "Transformation Digitale & Conseil",
      "home.expertises.transfo.desc": "Audit, roadmap, gouvernance et accompagnement pour passer un cap.",

      "home.sectors.title": "Secteurs",
      "home.sectors.subtitle": "Nous adaptons notre approche à ta maturité digitale, ton cycle de croissance et tes contraintes.",
      "home.sectors.startups.title": "Startups",
      "home.sectors.startups.desc": "Exécution rapide, scalabilité, acquisition et itérations produit.",
      "home.sectors.enterprise.title": "Grandes entreprises & institutions",
      "home.sectors.enterprise.desc": "Gouvernance, process, modernisation des outils et pilotage.",
      "home.sectors.ecom.title": "E-commerce",
      "home.sectors.ecom.desc": "Conversion, catalogue, automatisation, tracking et optimisation.",
      "home.sectors.cta": "Voir notre approche sectorielle →",

      "home.method.title": "Méthodologie",
      "home.method.subtitle": "Une exécution structurée, du diagnostic à la mise en production.",
      "home.method.s1.title": "Diagnostic",
      "home.method.s1.desc": "Compréhension du besoin, audit rapide, clarification des objectifs et contraintes.",
      "home.method.s2.title": "Roadmap & conception",
      "home.method.s2.desc": "Architecture, plan d’exécution, priorités et livrables. Alignement clair.",
      "home.method.s3.title": "Déploiement",
      "home.method.s3.desc": "Implémentation, tests, mise en ligne et configuration tracking si besoin.",
      "home.method.s4.title": "Optimisation",
      "home.method.s4.desc": "Suivi, itérations, amélioration performance et accompagnement.",

      "home.faq.title": "FAQ",
      "home.faq.subtitle": "Réponses aux questions les plus fréquentes avant une demande de devis.",
      "home.faq.q1": "Travaillez-vous uniquement au Togo ?",
      "home.faq.a1": "Non. Nous pouvons travailler à distance selon le projet, avec un cadre clair (brief, validation, livrables).",
      "home.faq.q2": "Pourquoi “sur devis” ?",
      "home.faq.a2": "Parce que le périmètre, les délais, le niveau d’intégration et les exigences varient fortement selon l’organisation.",
      "home.faq.q3": "Quels livrables fournissez-vous ?",
      "home.faq.a3": "Selon le projet : maquette, pages, tracking, documentation, recommandations et plan d’optimisation.",
      "home.faq.q4": "Pouvez-vous reprendre un projet existant ?",
      "home.faq.a4": "Oui, après audit : qualité du code, performance, sécurité, et refactorisation si nécessaire.",
      "home.faq.q5": "Quel est le délai moyen ?",
      "home.faq.a5": "Cela dépend du périmètre. Après le diagnostic, nous donnons un planning réaliste et découpé par étapes.",

      "home.final.title": "Prêt à démarrer ?",
      "home.final.subtitle": "Décris ton besoin et reçois une proposition structurée.",
      "home.final.cta": "Demander un devis",

      // CONTACT
      "contact.title": "Contact",
      "contact.subtitle": "Décris ton besoin et reçois une proposition structurée par email.",
      "contact.infoTitle": "Informations",
      "contact.infoEmailLabel": "Email",
      "contact.infoLocationLabel": "Localisation",
      "contact.infoLocationValue": "Lomé, Togo",
      "contact.note": "Conseil : si ton projet a un site existant, colle le lien dans ton message.",
      "contact.expectTitle": "À quoi s’attendre",
      "contact.expect1": "Lecture de ta demande et clarification si nécessaire",
      "contact.expect2": "Proposition structurée (périmètre, étapes, délais)",
      "contact.expect3": "Plan d’exécution et prochaines actions",
      "contact.formTitle": "Demander un devis",
      "contact.formSubtitle": "Champs simples : nom, email, message.",
      "contact.formNameLabel": "Nom",
      "contact.formEmailLabel": "Email",
      "contact.formMsgLabel": "Message",
      "contact.formSubmit": "Envoyer",
      "contact.formHint": "Tu recevras une réponse par email. Pense à vérifier les spams si besoin.",
      "contact.formNamePH": "Ton nom",
      "contact.formEmailPH": "tonmail@email.com",
      "contact.formMsgPH": "Décris ton besoin...",

      // EXP HUB
      "expHub.title": "Expertises",
      "expHub.subtitle": "Quatre piliers structurants pour concevoir, déployer et optimiser des systèmes digitaux performants.",
      "expHub.cta": "Voir l’expertise",
      "expHub.final.title": "Parlons de ton besoin",
      "expHub.final.subtitle": "Décris ton contexte et reçois une proposition structurée.",
      "expHub.final.cta": "Demander un devis",

      "expHub.web.title": "Architecture & Développement Web",
      "expHub.web.desc": "Conception et développement de sites et plateformes rapides, maintenables et scalables.",
      "expHub.web.p1": "Performance",
      "expHub.web.p2": "Scalabilité",
      "expHub.web.p3": "Conversion",
      "expHub.web.boxTitle": "Cas d’usage",
      "expHub.web.box1": "Site vitrine premium orienté conversion",
      "expHub.web.box2": "Landing pages pour acquisition",
      "expHub.web.box3": "Plateforme / portail interne",

      "expHub.acq.title": "Publicité & Acquisition Digitale",
      "expHub.acq.desc": "Stratégie, tracking et optimisation de campagnes orientées ROI (Meta/Google).",
      "expHub.acq.p1": "Tracking",
      "expHub.acq.p2": "Optimisation",
      "expHub.acq.p3": "ROI",
      "expHub.acq.boxTitle": "Cas d’usage",
      "expHub.acq.box1": "Génération de leads qualifiés",
      "expHub.acq.box2": "Acquisition e-commerce",
      "expHub.acq.box3": "Reporting & pilotage",

      "expHub.auto.title": "Automatisation & Digitalisation",
      "expHub.auto.desc": "Digitalisation de process, automatisations simples et outils pour gagner en efficacité.",
      "expHub.auto.p1": "Process",
      "expHub.auto.p2": "Fiabilité",
      "expHub.auto.p3": "Gain de temps",
      "expHub.auto.boxTitle": "Cas d’usage",
      "expHub.auto.box1": "Automatisation emails / suivi client",
      "expHub.auto.box2": "Outils internes et templates",
      "expHub.auto.box3": "Workflows simples (no-code)",

      "expHub.transfo.title": "Transformation Digitale & Conseil",
      "expHub.transfo.desc": "Audit, roadmap, gouvernance et accompagnement pour structurer le digital à l’échelle.",
      "expHub.transfo.p1": "Audit",
      "expHub.transfo.p2": "Roadmap",
      "expHub.transfo.p3": "Gouvernance",
      "expHub.transfo.boxTitle": "Cas d’usage",
      "expHub.transfo.box1": "Diagnostic de maturité digitale",
      "expHub.transfo.box2": "Modernisation des outils",
      "expHub.transfo.box3": "Pilotage & KPIs",

      // EXP PAGES CTA SECONDARY
      "about.hero.ctaSecondary": "Voir nos expertises",
      "contact.hero.ctaSecondary": "Voir les expertises",
      "expWeb.hero.ctaSecondary": "Retour aux expertises",
      "expAcq.hero.ctaSecondary": "Retour aux expertises",
      "expAuto.hero.ctaSecondary": "Retour aux expertises",
      "expTransfo.hero.ctaSecondary": "Retour aux expertises",
      "sectors.hero.ctaSecondary": "Voir nos expertises",

      // EXP WEB
      "expWeb.kicker": "Expertise",
      "expWeb.title": "Architecture & Développement Web",
      "expWeb.subtitle": "Conception et développement de systèmes web performants : vitesse, clarté, sécurité, évolutivité.",
      "expWeb.cta": "Demander un devis",
      "expWeb.problem.title": "Problèmes fréquents",
      "expWeb.problem.p1t": "Site lent",
      "expWeb.problem.p1d": "Mauvaise performance, UX dégradée, perte de conversions.",
      "expWeb.problem.p2t": "Architecture fragile",
      "expWeb.problem.p2d": "Difficulté à faire évoluer, bugs récurrents, maintenance coûteuse.",
      "expWeb.problem.p3t": "Manque de tracking",
      "expWeb.problem.p3d": "Décisions sans données : pas de mesure, pas d’optimisation.",
      "expWeb.solution.title": "Notre solution",
      "expWeb.solution.desc": "Nous construisons des sites et plateformes structurés, rapides et maintenables, avec une base technique solide et une logique orientée conversion (navigation, pages clés, formulaires).",
      "expWeb.solution.p1": "Performance",
      "expWeb.solution.p2": "Scalabilité",
      "expWeb.solution.p3": "Sécurité",
      "expWeb.solution.p4": "Conversion",
      "expWeb.method.title": "Méthodologie",
      "expWeb.method.s1t": "Audit & objectifs",
      "expWeb.method.s1d": "Analyse du besoin, pages, contenus, contraintes et KPI.",
      "expWeb.method.s2t": "Architecture",
      "expWeb.method.s2d": "Structure, navigation, composants, conventions et performance.",
      "expWeb.method.s3t": "Développement",
      "expWeb.method.s3d": "Implémentation, tests, accessibilité, SEO technique.",
      "expWeb.method.s4t": "Mise en ligne",
      "expWeb.method.s4d": "Déploiement, tracking, optimisation initiale, documentation.",
      "expWeb.final.title": "Tu veux un site solide et évolutif ?",
      "expWeb.final.subtitle": "Partage ton contexte, on te fait une proposition structurée.",
      "expWeb.final.cta": "Demander un devis",

      // EXP ACQ
      "expAcq.kicker": "Expertise",
      "expAcq.title": "Publicité & Acquisition Digitale",
      "expAcq.subtitle": "Tracking, stratégie et optimisation continue pour générer des leads/ventes de manière contrôlée.",
      "expAcq.cta": "Demander un devis",
      "expAcq.problem.title": "Problèmes fréquents",
      "expAcq.problem.p1t": "Dépenses sans ROI",
      "expAcq.problem.p1d": "Budget consommé sans résultat mesurable.",
      "expAcq.problem.p2t": "Tracking absent",
      "expAcq.problem.p2d": "Impossible de savoir ce qui convertit vraiment.",
      "expAcq.problem.p3t": "Ciblage imprécis",
      "expAcq.problem.p3d": "Leads non qualifiés, CPA élevé.",
      "expAcq.solution.title": "Notre solution",
      "expAcq.solution.desc": "Nous structurons l’acquisition autour de données : tracking, messages, audiences, tests et optimisation pour stabiliser la performance.",
      "expAcq.solution.p1": "Tracking",
      "expAcq.solution.p2": "Tests",
      "expAcq.solution.p3": "Optimisation",
      "expAcq.solution.p4": "Reporting",
      "expAcq.method.title": "Méthodologie",
      "expAcq.method.s1t": "Objectifs & tracking",
      "expAcq.method.s1d": "Définition KPI, pixels, events, UTM, analytics.",
      "expAcq.method.s2t": "Stratégie & messages",
      "expAcq.method.s2d": "Offre, copy, angles, pages de destination.",
      "expAcq.method.s3t": "Campagnes & tests",
      "expAcq.method.s3d": "Lancement, A/B tests, itérations.",
      "expAcq.method.s4t": "Optimisation & reporting",
      "expAcq.method.s4d": "Amélioration continue, rapport clair et actions.",
      "expAcq.final.title": "Tu veux une acquisition maîtrisée ?",
      "expAcq.final.subtitle": "Décris ton objectif et ton contexte, on te propose un plan.",
      "expAcq.final.cta": "Demander un devis",

      // EXP AUTO
      "expAuto.kicker": "Expertise",
      "expAuto.title": "Automatisation & Digitalisation",
      "expAuto.subtitle": "Process clairs, outils simples et automatisations pour réduire les frictions et améliorer la fiabilité.",
      "expAuto.cta": "Demander un devis",
      "expAuto.problem.title": "Problèmes fréquents",
      "expAuto.problem.p1t": "Trop manuel",
      "expAuto.problem.p1d": "Perte de temps, erreurs, double saisie.",
      "expAuto.problem.p2t": "Process flous",
      "expAuto.problem.p2d": "Exécution incohérente, dépendance aux personnes.",
      "expAuto.problem.p3t": "Outils dispersés",
      "expAuto.problem.p3d": "Données éclatées, suivi difficile.",
      "expAuto.solution.title": "Notre solution",
      "expAuto.solution.desc": "Nous cartographions tes flux, choisissons des outils adaptés et mettons en place des automatisations simples (no-code/low-code) pour stabiliser l’exécution.",
      "expAuto.solution.p1": "Process",
      "expAuto.solution.p2": "Automatisation",
      "expAuto.solution.p3": "Fiabilité",
      "expAuto.solution.p4": "Documentation",
      "expAuto.method.title": "Méthodologie",
      "expAuto.method.s1t": "Cartographie",
      "expAuto.method.s1d": "Flux, rôles, points de friction, priorités.",
      "expAuto.method.s2t": "Design process",
      "expAuto.method.s2d": "Procédures, templates, standards et règles.",
      "expAuto.method.s3t": "Automatisation",
      "expAuto.method.s3d": "Workflows, intégrations, alertes, synchronisation.",
      "expAuto.method.s4t": "Déploiement",
      "expAuto.method.s4d": "Tests, documentation, formation et amélioration.",
      "expAuto.final.title": "Tu veux gagner en efficacité ?",
      "expAuto.final.subtitle": "Explique tes process actuels et ce que tu veux améliorer.",
      "expAuto.final.cta": "Demander un devis",

      // EXP TRANSFO
      "expTransfo.kicker": "Expertise",
      "expTransfo.title": "Transformation Digitale & Conseil",
      "expTransfo.subtitle": "Clarifier la direction, structurer une roadmap, mettre en place une gouvernance et exécuter par étapes.",
      "expTransfo.cta": "Demander un devis",
      "expTransfo.problem.title": "Problèmes fréquents",
      "expTransfo.problem.p1t": "Pas de roadmap",
      "expTransfo.problem.p1d": "Actions dispersées, priorités instables.",
      "expTransfo.problem.p2t": "Gouvernance faible",
      "expTransfo.problem.p2d": "Décisions lentes, ownership flou, exécution irrégulière.",
      "expTransfo.problem.p3t": "Outils non alignés",
      "expTransfo.problem.p3d": "Technos et process qui ne servent pas les objectifs.",
      "expTransfo.solution.title": "Notre solution",
      "expTransfo.solution.desc": "Nous cadrons la transformation (objectifs, périmètre, KPI), construisons une roadmap réaliste, puis déployons avec une gouvernance et des rituels de pilotage.",
      "expTransfo.solution.p1": "Audit",
      "expTransfo.solution.p2": "Roadmap",
      "expTransfo.solution.p3": "Pilotage",
      "expTransfo.solution.p4": "KPIs",
      "expTransfo.method.title": "Méthodologie",
      "expTransfo.method.s1t": "Diagnostic",
      "expTransfo.method.s1d": "Maturité digitale, risques, quick wins.",
      "expTransfo.method.s2t": "Roadmap",
      "expTransfo.method.s2d": "Priorités, budget, planning, dépendances.",
      "expTransfo.method.s3t": "Gouvernance",
      "expTransfo.method.s3d": "Rôles, rituels, décisions, reporting.",
      "expTransfo.method.s4t": "Exécution",
      "expTransfo.method.s4d": "Déploiement par étapes + optimisation.",
      "expTransfo.final.title": "Tu veux structurer ta transformation ?",
      "expTransfo.final.subtitle": "Décris ton contexte et tes objectifs, on te propose une roadmap.",
      "expTransfo.final.cta": "Demander un devis",

      // SECTORS
      "sectors.title": "Secteurs",
      "sectors.subtitle": "Chaque organisation a une maturité digitale différente. Notre approche s’adapte au contexte, au cycle de croissance et aux contraintes.",
      "sectors.approachTitle": "Notre approche sectorielle",
      "sectors.s1.title": "Startups",
      "sectors.s1.desc": "Rapidité d’exécution, scalabilité, acquisition et itérations produit.",
      "sectors.s2.title": "Grandes entreprises & institutions",
      "sectors.s2.desc": "Gouvernance digitale, optimisation des process, modernisation des outils et pilotage.",
      "sectors.s3.title": "E-commerce",
      "sectors.s3.desc": "Performance, conversion, automatisation, tracking et optimisation continue.",
      "sectors.adaptTitle": "Méthodologie d’adaptation",
      "sectors.m1.title": "Audit sectoriel",
      "sectors.m1.desc": "Comprendre l’activité, les enjeux, les contraintes et les priorités.",
      "sectors.m2.title": "Analyse maturité",
      "sectors.m2.desc": "Évaluer l’existant : outils, process, canaux et données.",
      "sectors.m3.title": "Roadmap",
      "sectors.m3.desc": "Prioriser, cadrer, planifier et définir des livrables concrets.",
      "sectors.m4.title": "Déploiement",
      "sectors.m4.desc": "Exécuter par étapes, mesurer, améliorer.",
      "sectors.final.title": "Parlons de ton secteur",
      "sectors.final.subtitle": "Décris ton contexte et on te propose une approche adaptée.",
      "sectors.final.cta": "Demander un devis",

      // ABOUT
      "about.title": "À propos",
      "about.subtitle": "Up Tech Group aide les organisations à structurer, déployer et optimiser leur système digital.",
      "about.visionTitle": "Vision",
      "about.visionText": "Parce que le numérique n’est pas un luxe, mais une nécessité. Nous croyons à un digital utile, structuré, mesurable et orienté résultat.",
      "about.missionTitle": "Mission",
      "about.m1t": "Structurer",
      "about.m1d": "Clarifier l’architecture, les process et la roadmap.",
      "about.m2t": "Déployer",
      "about.m2d": "Livrer des solutions web, acquisition et automatisation.",
      "about.m3t": "Optimiser",
      "about.m3d": "Mesurer, améliorer et faire évoluer avec méthode.",
      "about.valuesTitle": "Valeurs",
      "about.v1t": "Clarté",
      "about.v1d": "Objectifs, livrables et responsabilités clairement définis.",
      "about.v2t": "Exécution",
      "about.v2d": "Livrer, itérer, améliorer — sans complexité inutile.",
      "about.v3t": "Performance",
      "about.v3d": "Mesure, conversion, efficacité opérationnelle.",
      "about.v4t": "Fiabilité",
      "about.v4d": "Solutions maintenables, documentation, continuité.",
      "about.final.title": "Tu as un projet ?",
      "about.final.subtitle": "Explique ton besoin, on te répond rapidement par email.",
      "about.final.cta": "Demander un devis"
    },

    en: {
      // NAV
      "nav.home": "Home",
      "nav.expertises": "Expertise",
      "nav.sectors": "Sectors",
      "nav.about": "About",
      "nav.contact": "Contact",

      // FOOTER
      "footer.tagline": "Digital is not a luxury — it’s a necessity.",
      "footer.location": "Lomé, Togo",
      "footer.contact": "Contact",

      // HOME
      "home.kicker": "Digital transformation • Technology • Delivery",
      "home.title": "We help organizations succeed in their digital transformation.",
      "home.subtitle": "From web delivery to acquisition, automation and consulting, we build structured, scalable, performance-driven digital solutions.",
      "home.ctaPrimary": "Request a quote",
      "home.ctaSecondary": "Explore our expertise",

      "home.metric1.label": "Approach",
      "home.metric1.value": "Tech & strategy",
      "home.metric1.note": "Architecture, systems, governance.",
      "home.metric2.label": "Focus",
      "home.metric2.value": "Performance",
      "home.metric2.note": "Conversion, tracking, optimization.",
      "home.metric3.label": "Outcome",
      "home.metric3.value": "Clear delivery",
      "home.metric3.note": "Roadmap, delivery, iteration.",

      "benefits.title": "What you get",
      "benefits.subtitle": "Structured execution, data-driven decisions, and a technical foundation that lasts.",
      "benefits.b1.title": "Clarity",
      "benefits.b1.desc": "Scope, deliverables, priorities and timeline clearly defined.",
      "benefits.b2.title": "Measurement",
      "benefits.b2.desc": "Tracking, KPIs, and continuous optimization.",
      "benefits.b3.title": "Reliability",
      "benefits.b3.desc": "Maintainable, documented, scalable systems.",

      // CONTACT
      "contact.title": "Contact",
      "contact.subtitle": "Describe your needs and receive a structured proposal by email.",
      "contact.infoTitle": "Information",
      "contact.infoEmailLabel": "Email",
      "contact.infoLocationLabel": "Location",
      "contact.infoLocationValue": "Lomé, Togo",
      "contact.note": "Tip: if you already have a website, paste the link in your message.",
      "contact.expectTitle": "What to expect",
      "contact.expect1": "We review your request and clarify if needed",
      "contact.expect2": "A structured proposal (scope, steps, timeline)",
      "contact.expect3": "An execution plan and next actions",
      "contact.formTitle": "Request a quote",
      "contact.formSubtitle": "Simple fields: name, email, message.",
      "contact.formNameLabel": "Name",
      "contact.formEmailLabel": "Email",
      "contact.formMsgLabel": "Message",
      "contact.formSubmit": "Send",
      "contact.formHint": "You will receive a reply by email. Check spam if needed.",
      "contact.formNamePH": "Your name",
      "contact.formEmailPH": "you@email.com",
      "contact.formMsgPH": "Describe your needs...",

      // CTA SECONDARY
      "about.hero.ctaSecondary": "See our expertise",
      "contact.hero.ctaSecondary": "See expertise",
      "expWeb.hero.ctaSecondary": "Back to expertise",
      "expAcq.hero.ctaSecondary": "Back to expertise",
      "expAuto.hero.ctaSecondary": "Back to expertise",
      "expTransfo.hero.ctaSecondary": "Back to expertise",
      "sectors.hero.ctaSecondary": "See our expertise"
    }
  };

  // Storage helpers
  function getLang() {
    return localStorage.getItem("lang") || "fr";
  }
  function setLang(lang) {
    localStorage.setItem("lang", lang);
  }

  // Translation helper
  function t(key, lang = getLang()) {
    return (I18N[lang] && Object.prototype.hasOwnProperty.call(I18N[lang], key))
      ? I18N[lang][key]
      : null;
  }

  // Apply translations
  function applyI18n(lang = getLang()) {
    document.querySelectorAll("[data-i18n]").forEach((el) => {
      const key = el.getAttribute("data-i18n");
      const val = t(key, lang);
      if (val !== null) el.textContent = val;
    });

    document.querySelectorAll("[data-i18n-placeholder]").forEach((el) => {
      const key = el.getAttribute("data-i18n-placeholder");
      const val = t(key, lang);
      if (val !== null) el.setAttribute("placeholder", val);
    });

    document.querySelectorAll("[data-i18n-html]").forEach((el) => {
      const key = el.getAttribute("data-i18n-html");
      const val = t(key, lang);
      if (val !== null) el.innerHTML = val;
    });

    document.documentElement.setAttribute("lang", lang);

    const label = document.getElementById("langLabel");
    if (label) label.textContent = lang.toUpperCase();
  }

  // Setup toggle
  function setupLangToggle() {
    const btn = document.getElementById("langToggle");
    if (!btn) return;
    btn.addEventListener("click", () => {
      const next = getLang() === "fr" ? "en" : "fr";
      setLang(next);
      applyI18n(next);
    });
  }

  // Expose globally (for main.js compatibility)
  window.I18N = I18N;
  window.getLang = getLang;
  window.setLang = setLang;
  window.t = t;
  window.applyI18n = applyI18n;
  window.setupLangToggle = setupLangToggle;

  // Auto-init
  document.addEventListener("DOMContentLoaded", () => {
    setupLangToggle();
    applyI18n(getLang());
  });
})();