import type {
  DeclarationDraftRowDTO,
  DeclarationTypeCardDTO,
} from "../contracts/ResearchDeclarationGateway.contract";
import {
  declarationTypeCardsMockDTO,
  draftDeclarationsMockDTO,
} from "../mock-data/researchDeclarationGateway.mock";

function sleep(ms: number) {
  return new Promise<void>((resolve) => window.setTimeout(resolve, ms));
}

export async function getDeclarationTypeCardsDTO(): Promise<
  DeclarationTypeCardDTO[]
> {
  await sleep(250);
  return declarationTypeCardsMockDTO;
}

export async function getDraftDeclarationsDTO(): Promise<
  DeclarationDraftRowDTO[]
> {
  await sleep(300);
  return draftDeclarationsMockDTO;
}
