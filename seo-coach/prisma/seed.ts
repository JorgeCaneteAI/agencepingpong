import { PrismaClient } from "@prisma/client";

const prisma = new PrismaClient();

async function main() {
  const sites = [
    {
      url: "https://villaplaisance.fr",
      name: "Villa Plaisance",
      objective: "Réservations chambres d'hôtes",
      theme: "Hébergement / tourisme",
      geoZone: "Uzès, Gard",
    },
    {
      url: "https://yelloevent.fr",
      name: "YelloEvent",
      objective: "Demandes de devis traiteur/mariage",
      theme: "Traiteur / décoration / mariage",
      geoZone: "Nîmes, Gard",
    },
    {
      url: "https://canete.fr",
      name: "Canete Conciergerie",
      objective: "Contact propriétaires Airbnb",
      theme: "Conciergerie / gestion de propriété",
      geoZone: "Uzès, Gard",
    },
    {
      url: "https://agencepingpong.fr",
      name: "Agence Ping Pong",
      objective: "Acquisition clients web",
      theme: "Agence web / développement",
      geoZone: "France entière",
    },
  ];

  for (const site of sites) {
    const existing = await prisma.project.findFirst({
      where: { url: site.url },
    });

    if (existing) {
      await prisma.project.update({
        where: { id: existing.id },
        data: site,
      });
    } else {
      await prisma.project.create({ data: site });
    }
  }

  console.log("Seed completed: 4 projects created");
}

main()
  .catch((e) => {
    console.error(e);
    process.exit(1);
  })
  .finally(() => prisma.$disconnect());
