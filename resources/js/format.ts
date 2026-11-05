/** Replace :placeholders in a translated string: format("Add :type", { type: "page" }) */
export function format(template: string, values: Record<string, string | number>): string {
  return Object.entries(values).reduce((text, [key, value]) => text.split(`:${key}`).join(String(value)), template)
}
